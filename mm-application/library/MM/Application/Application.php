<?php
/**
 * @author Marian Meres
 */
namespace MM\Application;

use MM\Controller\AbstractController;
use MM\Controller\Exception\PageNotFound;
use MM\Util\ClassUtil;
use MM\Controller\Params;

/**
 * Class Application
 * @package MM\Application
 *
 * V podstate velmi simple container s callbackmi a ich resultami. Callbacky sa
 * vykonaju len raz, inak sa vracaju uz existujue resulty. Plus zopar utilitiek.
 */
class Application
{
    /**
     * Pole zaregistrovanych "init" callbackov
     * @var array
     */
    protected $_callbacks = array();

    /**
     * Interny kontajner callbackovych resultov
     * @var array
     */
    protected $_executed = array();

    /**
     * Taky trosku hack na special case-y... ak bude setnuty, tak bude pouzity
     * namiesto defaultneho self::decomposeRequestCallback
     */
    public static $decomposeRequestCallback;

    /**
     * @param array $callbacks
     */
    public function __construct(array $callbacks = array())
    {
        foreach ($callbacks as $name => $callback) {
            $this->init($name, $callback);
        }
    }

    /**
     * @param array $callbacks
     * @param array $names
     * @return Application
     */
    public static function factory(array $callbacks = array(), array $names = null)
    {
        $app = new self($callbacks);
        $app->bootstrap($names);
        return $app;
    }

    /**
     * Vykona vsetky (alebo len specifikovane) zaregistrovane bootstrap/init
     * callbacky
     *
     * @param array $names
     * @return $this
     * @throws Exception
     */
    public function bootstrap(array $names = null)
    {
        // null means all!
        if (null === $names) {
            $names = array_keys($this->_callbacks);
        }

        foreach ($names as $name) {
            $this->_execute($name);
        }

        return $this;
    }

    /**
     * Vykona zaregistrovany callback podla mena. Iba raz.
     *
     * @param $name
     * @return mixed
     * @throws Exception
     */
    protected function _execute($name)
    {
        $name = strtolower($name);

        if (!isset($this->_callbacks[$name])) {
            throw new Exception("Callback for '$name' not (yet) defined");
        }

        if (array_key_exists($name, $this->_executed)) {
            return $this->_executed[$name];
        }

        // @note: null result je legitimny
        $result = call_user_func_array($this->_callbacks[$name], array($this));

        return $this->_executed[$name] = $result;
    }

    /**
     * Zaregistruje callback podla mena.
     *
     * @param $name
     * @param \Closure|null $callback
     * @return $this
     */
    protected function _register($name, \Closure $callback = null)
    {
        $name = strtolower($name);
        $this->_callbacks[$name] = $callback;
        return $this;
    }

    /**
     * Bud zaregistruje callback, alebo ho vykona (alebo interne vrati uz
     * vykonany result ak to volame viac krat)
     *
     * @param $name
     * @param \Closure|null $callback
     * @return mixed|Application
     * @throws Exception
     */
    public function init($name, \Closure $callback = null)
    {
        $name = strtolower($name);

        // ak callback, tak ho zaregistruj...
        if ($callback) {
            return $this->_register($name, $callback);
        }

        // inak (je uz asi zaregitrovany, tak) ho vykonaj
        return $this->_execute($name);
    }

    /**
     * Explicitnejsi alias na init bez callbacku (co vracia uz vykonany result)
     *
     * @param $name
     * @return mixed|Application
     */
    public function get($name)
    {
        return $this->init($name);
    }

    /**
     * Helper for conventional use. Safely override
     *
     * @param array|null $request
     * @param array|null $server
     * @param string $aSeparator
     * @param string $controllerNs
     * @param string $baseControllerFallback
     * @return AbstractController
     */
    public function factoryController(array $request = null, array $server = null,
                                      $aSeparator = '.',
                                      $controllerNs = '\App\{Module}\Controller\\',
                                      $baseControllerFallback = '\App\Controller')
    {
        $request = $request ?: array_merge($_GET, $_POST, $_REQUEST);
        $request = self::decomposeRequest($request, $aSeparator);

        $replaceMap = [
            '{Module}'     => ucfirst(strtolower($request['_module'])),
            '{Controller}' => ucfirst(strtolower($request['_controller'])),
            '{Action}'     => ucfirst(strtolower($request['_action'])),
        ];
        $controllerNs = str_replace(
            array_keys($replaceMap), array_values($replaceMap), $controllerNs
        );
        $controllerNs = preg_replace('/\\\\+/', '\\', $controllerNs);

        // vyskladame nazov classu buduceho controllera
        $controllerClass = $controllerNs . str_replace(' ', '', ucwords(str_replace(
                array(".", "-", '/'), " ", ucfirst(strtolower($request['_controller']))
            ))) . "Controller";

        // novinka: "_" v nazve kontrollera mapujeme ako namespace (aby sa dali
        // kontrolere filesystemovo upratat)
        $controllerClass = str_replace(' ', '\\', ucwords(
            str_replace("_", " ", $controllerClass)
        ));
        // prx($controllerClass);

        if (!ClassUtil::classExists($controllerClass)) {
            $exception = new PageNotFound("Controller '$controllerClass' not found");
        }

        // ak chyba, tak (optimisticky) konvencne defaultny
        if (!empty($exception)) {
            $controllerClass = $baseControllerFallback;
        }

        // konvencia
        $dic = $this->init("dic");

        // params reference chicken-egg fix
        /** @var Params $params */
        $params = $dic['params'];
        $params->set($request);

        // unit testable
        if ($server) {
            $params->_SERVER()->exchangeArray($server);
        }

        //
        return new $controllerClass($params, array(
            'exception' => isset($exception) ? $exception : null,
            'response'  => isset($dic['response']) ? $dic['response'] : null,
            'dic'       => $dic,
        ));
    }

    /**
     * Routing riesime normalne via request parametre "_controller" a "_action",
     * ziadne fancy tanečky... s jedinou vynimkou: ak je setnuty request[a], tak
     * ten ma vyssiu prioritu a ma defaultne tvar module[.controller[.action]]
     * (pri custom tvare treba pouzit custom $decomposeRequestCallback)
     *
     * @param array $request
     * @param string $aSeparator
     * @return array|mixed
     */
    public static function decomposeRequest(array $request, $aSeparator = '.',
                                            $decomposeStrategyControllerFirst = false)
    {
        // return early with custom decomposition, if provided
        if (is_callable(self::$decomposeRequestCallback)) {
            return call_user_func_array(
                self::$decomposeRequestCallback, array($request)
            );
        }

        $r = $request;
        $r['_module']     = !empty($r['_module'])     ? $r['_module']     : 'index';
        $r['_controller'] = !empty($r['_controller']) ? $r['_controller'] : 'index';
        $r['_action']     = !empty($r['_action'])     ? $r['_action']     : 'index';

        if (!empty($r['a'])) {
            $parts = explode($aSeparator, $r['a']);

            // "controller" first approach
            if ($decomposeStrategyControllerFirst) {
                if (1 == count($parts)) {
                    $r['_controller'] = $parts[0];
                }
                elseif (2 == count($parts)) {
                    $r['_controller'] = $parts[0];
                    $r['_action'] = $parts[1];
                }
                else {
                    $r['_module'] = $parts[0];
                    $r['_controller'] = $parts[1];
                    array_splice($parts, 0, 2);
                    $r['_action'] = join($aSeparator, $parts);
                }
            }
            // module first
            else {
                if (1 == count($parts)) {
                    $r['_module'] = $parts[0];
                }
                elseif (2 == count($parts)) {
                    $r['_module'] = $parts[0];
                    $r['_controller'] = $parts[1];
                }
                else {
                    $r['_module'] = $parts[0];
                    $r['_controller'] = $parts[1];
                    array_splice($parts, 0, 2);
                    $r['_action'] = join($aSeparator, $parts);
                }
            }
        }

        $r['a'] = implode($aSeparator, [$r['_module'], $r['_controller'], $r['_action']]);

        return $r;
    }


    /**
     * Podobne ako array_merge_recursive, akurat vzdy prepisuje kluce (co je pre load
     * configu zasadne)
     *
     * Inspirovane z:
     * http://www.php.net/manual/en/function.array-merge-recursive.php#104145
     *
     * @return array
     */
    public static function mergeConfig()
    {
        if (func_num_args() < 2) {
            trigger_error(__METHOD__ . ' needs two or more array arguments', E_USER_WARNING);
            return false;
        }

        $arrays = func_get_args();
        $merged = array();

        while ($arrays) {
            $array = array_shift($arrays);
            if (!is_array($array)) {
                trigger_error(__METHOD__ . ' encountered a non array argument', E_USER_WARNING);
                return false;
            }
            foreach ($array as $key => $value) {
                //if (is_string($key)) {
                    if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key])) {
                        $merged[$key] = call_user_func(__METHOD__, $merged[$key], $value);
                    } else {
                        $merged[$key] = $value;
                    }
                //} else {
                //    $merged[] = $value;
                //}
            }
        }
        return $merged;
    }
}
