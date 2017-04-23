<?php
/**
 * @author Marian Meres
 */
namespace MM\Session;

use MM\Controller\Response;
use MM\Session\SaveHandlerInterface as SaveHandler;

/**
 * Nuze... toto je staticky wrap nad session_* funkciami primarne kvoli moznosti
 * sessiony nejak rozumne testovat (inak automaticky setuju cookie). Okrem
 * moznosti testovania pridava "namespace" featury, ale inak je to naozaj
 * trivialny obal
 *
 * Note: pozeral a vrtal som sa v implementaciach Nette, Zf1, Zf2 a SYmphony...
 * a zasadny problem co potrebujem vyriesit (testovanie) ani jeden elegantne
 * neriesi (prilis komplikovane az vobec)... sice pridavaju ine featury ale tie
 * neriesim
 *
 * Note2: Z definicie nativneho php handlovania sessions je staticky pristup
 * myslim idealny (co je skor vynimka) kedze $_SESSION je superglobal tak ci tak...
 * nehovoriac o session_* funkciach
 *
 * Note3: nizsie davam popisy z php manualu, aby sme mali na ociach co php
 * interne robi
 *
 * Note4: nepaci sa mi to ale. Idealne by bolo cely session_* manazment nepouzivat
 * (to je cele trivialne), ale kopec legacy aj 3-to stranovych veci to pouziva
 * (napr. FB sdk), takze uplne ignorovat to nejde
 *
 */
class Session
{
    /**
     * Interny flag, ci ideme v "mockovacom" rezime - tj, realne session_* fnc
     * vobec nebudeme volat
     * @var boolean
     */
    protected static $_mock = false;

    /**
     * Interny flag, ci sme uz nastartovali
     * @var boolean
     */
    protected static $_started = null;

    /**
     * session_name
     * @var string
     */
    protected static $_name;

    /**
     * session_id
     * @var string
     */
    protected static $_id = '';

    /**
     * @var \MM\Session\SaveHandlerInterface
     */
    protected static $_saveHandler;

    /**
     * @var null|Response
     */
    protected static $_response;

    /**
     * key under which some meta data are stored under $_SESSION
     * @var string
     */
    protected static $_nsSys = '__MM_SESSION__';

    /**
     * key under which userland session data are stored under $_SESSION
     * @var string
     */
    protected static $_nsUsr = '__MM_SESSION_USR__';

    /**
     * Reset na vychodzi stav. Vyuzitie pri testoch primarne
     */
    public static function resetToOutOfTheBoxState($mock = true)
    {
        self::$_started        = null;
        self::$_name           = null;
        self::$_id             = '';
        self::$_mock           = (bool) $mock;
        self::$_saveHandler    = null;
        self::$_response       = null;

        ini_set('session.cookie_lifetime', 0);
        $_SESSION = array();
    }

    /**
     * Explicitny alias
     */
    public static function resetWithUnitTestModeEnabled()
    {
        self::resetToOutOfTheBoxState($mock = true);
    }

    /**
     * session_start() creates a session or resumes the current one based on
     * a session identifier passed via a GET or POST request, or passed via
     * a cookie.
     *
     * When session_start() is called or when a session auto starts, PHP will
     * call the open and read session save handlers. These will either be
     * a built-in save handler provided by default or by PHP extensions (such
     * as SQLite or Memcached); or can be custom handler as defined by
     * session_set_save_handler(). The read callback will retrieve any
     * existing session data (stored in a special serialized format) and
     * will be unserialized and used to automatically populate the $_SESSION
     * superglobal when the read callback returns the saved session data back
     * to PHP session handling.
     *
     * To use a named session, call session_name() before calling
     * session_start().
     *
     * When session.use_trans_sid is enabled, the session_start() function
     * will register an internal output handler for URL rewriting.
     *
     * If a user uses ob_gzhandler or similar with ob_start(), the function
     * order is important for proper output. For example, ob_gzhandler must
     * be registered before starting the session.
     *
     * @throws Exception
     */
    public static function start()
    {
        if (self::$_started) {
            return;
        }

        // 2016 - ak php signalizuje, ze sessna je aktivna (asi bola nastartovana
        // mimo tento class), tak si upravim interne flagy a pokracujeme...
        if (session_status() == \PHP_SESSION_ACTIVE) {
            self::$_started = true;
            self::$_id = session_id();
            self::$_mock = false;
        }


        if (!self::$_mock && !self::$_started && !session_start()) {
            throw new Exception("Unable to start the session");
        }

        if (self::$_mock && !self::$_id) {
            self::setId(md5(uniqid("", true)));
        }

        self::$_started = true;

        // toto moze nastat ak sme mock a teda session_start vyssie nezbehol
        // aj takymto rucnym setnutim sa okamzite stava superglobalom
        if (!isset($_SESSION)) {
            $_SESSION = array();
        }

        // http://framework.zend.com/manual/1.12/en/zend.session.global_session_management.html#zend.session.global_session_management.session_identifiers.hijacking_and_fixation
        if (!isset($_SESSION[self::$_nsSys])) {
            $_SESSION[self::$_nsSys] = array(
                'initialized' => time()
            );
            self::regenerateId(true); // toto posiela cookie
        } else {
            self::setCookie();
        }
    }

    /**
     * Bola uz session nastartovana? Pozera na vlastny flag ak je rozdielny od
     * null
     *
     * @return bool
     */
    public static function isStarted()
    {
        if (null !== self::$_started) {
            return self::$_started;
        }

        // toto odchyti pripady ak bola sessna nastartovana mimo tento wrap
        // ale ak sme mock tak to ignorujeme
        if (!self::$_mock) {
            return session_status() == \PHP_SESSION_ACTIVE;
            //return defined("SID");
        }

        // tu najskor nie... (tu mozeme byt iba ak sme rucne medzicasom resetovali)
        return self::$_started = false;
    }

    /**
     * Get and/or set the current session id
     *
     * If id is specified, it will replace the current session id.
     * session_id() needs to be called before session_start() for that
     * purpose. Depending on the session handler, not all characters are allowed
     * within the session id. For example, the file session handler only allows
     * characters in the range a-z A-Z 0-9 , (comma) and - (minus)!
     *
     * Note: When using session cookies, specifying an id for session_id() will
     * always send a new cookie when session_start() is called, regardless if
     * the current session id is identical to the one being set.
     * @param $id
     * @throws Exception
     */
    public static function setId($id)
    {
        /*if (!self::$_mock) {
            if (self::isStarted()) {
                throw new Exception("Cannot set id - session already started");
            }
            session_id($id);
        }*/

        // volanie pred startom je safe - neposiela nic
        session_id($id);

        self::$_id = $id;
    }

    /**
     *
     */
    public static function getId()
    {
        if (!self::$_mock) {
            // return session_id();
            self::$_id = session_id();
        }

        return self::$_id;
    }

    /**
     * session_name() returns the name of the current session. If name is
     * given, session_name() will update the session name and return the old
     * session name.
     *
     * The session name is reset to the default value stored in session.name at
     * request startup time. Thus, you need to call session_name() for every
     * request (and before session_start() or session_register() are called).
     *
     * @return string
     */
    public static function getName()
    {
        if (!self::$_mock) {
            return session_name();
        }
        if (null === self::$_name) {
            self::$_name = ini_get("session.name");
        }
        return self::$_name;
    }

    /**
     * @param $name
     * @return string
     * @throws Exception
     */
    public static function setName($name)
    {
        if (self::isStarted()) {
            throw new Exception("Cannot set name - session already started");
        }

        $previous = self::getName();

        self::$_name = $name;

        if (!self::$_mock) {
            $previous = session_name($name);
        }

        return $previous;
    }

    /**
     * @param SaveHandlerInterface $saveHandler
     * @throws Exception
     */
    public static function setSaveHandler(SaveHandler $saveHandler)
    {
        if (self::isStarted()) {
            throw new Exception(
                "Cannot set saveHandler - session already started");
        }
        self::$_saveHandler = $saveHandler;

        // aj sa zaregistrujeme, ale shutdown rucne sem (kvoli potencialnemu
        // mock modu)
        self::$_saveHandler->registerSaveHandler($registerShutdown = false);
        register_shutdown_function(array(__CLASS__, 'writeClose'));
    }

    /**
     * @return SaveHandlerInterface
     */
    public static function getSaveHandler()
    {
        return self::$_saveHandler;
    }

    /**
     * @param Response $response
     */
    public static function setResponse(Response $response)
    {
        self::$_response = $response;
    }

    /**
     * @return null|Response
     */
    public static function getResponse()
    {
        return self::$_response;
    }

    /**
     * Update the current session id with a newly generated one
     *
     * session_regenerate_id() will replace the current session id with a new
     * one, and keep the current session information.
     *
     * @param bool $deleteOld
     * @return bool
     * @throws Exception
     */
    public static function regenerateId($deleteOld = false)
    {
        if (!self::$_mock && !session_regenerate_id($deleteOld)) {
            throw new Exception("Unable to regenerate session id");
        } else {
            self::$_id = md5(uniqid("", true));
        }
        self::setCookie();
        return true;
    }

    /**
     * End the current session and store session data.
     *
     * Session data is usually stored after your script terminated without the
     * need to call session_write_close(), but as session data is locked to
     * prevent concurrent writes only one script may operate on a session at
     * any time. When using framesets together with sessions you will experience
     * the frames loading one by one due to this locking. You can reduce the
     * time needed to load all the frames by ending the session as soon as all
     * changes to session variables are done.
     */
    public static function writeClose()
    {
        if (!self::$_mock) {
            session_write_close();
            return;
        }
        self::$_started = false;
    }

    /**
     * session_destroy() destroys all of the data associated with the current
     * session. It does not unset any of the global variables associated with
     * the session, or unset the session cookie. To use the session variables
     * again, session_start() has to be called.
     *
     * In order to kill the session altogether, like to log the user out, the
     * session id must also be unset. If a cookie is used to propagate the
     * session id (default behavior), then the session cookie must be deleted.
     * setcookie() may be used for that.
     *
     * @throws Exception
     */
    public static function destroy()
    {
        self::start();

        if (!self::$_mock && !session_destroy()) {
            throw new Exception("Unable to destroy session");
        }
        self::forgetMe();
    }

    /**
     * PROBLEM: chceme urdzat aktivnu sessnu dlhsi cas... (po zavreti browsera
     * a pod) a.k.a. "keep me logged in" alebo "remember me"
     *
     * Tato zdanliva drobnost je mierne zamotana zalezitost... okrem mnozstva
     * principialnych security issues, su tam aj nejake implementacne srandy
     *
     * SPOSOB TEJTO MM IMPLEMENTACIE:
     * - session cookie musime spravne setnut dlhu expiraciu
     * - save handler musi vediet spolupracovat, lebo inak je velka
     *   pravdepodobnost, ze garbage collect sessny zmaze... co sa sice da
     *   nastavit pausalnym zvysenim gc lifetime-u, ale to mi pride jednak
     *   neefektivne (smeti sa budu zbytocne hromadit) a az takmer nemozne
     *   (co ak chceme remember-me setnut na 1 rok? - potom nam smeti uz uplne
     *   prerastu cez hlavu... NOTE: ano, remember-me na 1 rok je nezmysel)
     *   NOTE: MM\Session\SaveHandler\DbTable (UniqueAddon) spolupracuje
     *
     * Vyssie je pomerne bezproblemovo riesitelne, drobny implementacny zadrhel
     * vyskakuje v momente ked si uvedomime bezny flow:
     *
     * A (spracovanie login formu)
     * 1. startujeme sessnu s defaultnymi optionami (de-facto pausalne vzdy),
     *    najma ide o hodnotu cookie_lifetime
     * 2. login data name sedia, do sessiony ulozime usera, posleme session
     *    cookie s dlhou expiraciou
     * 3. redirectneme niekam na B
     *
     * B (uz nalogovany user)
     * 1. startujeme sessnu ... no a tu je ten problem... ako tu zistit, aky
     *    cookie_lifetime nastavit? Pausalne by sme automaticky nastartovali
     *    s defaultnymi optionami, co vsak v praxi znamena, ze vyssi "remember
     *    me" lifetime z A efektivne prepiseme a ziadne remember me sa nekona...
     *
     * RIESENIE:
     * Hodnotu lifetime ktoru potrebujeme zistit v B si budeme priebezne ukladat
     * priamo do samotnej sessiony... akurat metodka setCookie na to musi pamatat
     *
     * @param $ttl
     */
    public static function rememberMe($ttl)
    {
        self::setCookieLifetime($ttl);
    }

    /**
     * Nastavi zapornu hodnotu cookie lifetime
     */
    public static function forgetMe()
    {
        self::setCookieLifetime(-1);
    }

    /**
     * @param $ttl
     */
    public static function setCookieLifetime($ttl)
    {
        $ttl = (int) $ttl;

        // saveHandler takisto potrebuje tento udaj aby vedel koretne spolupracovat
        // co v praxi znamena, ze garbage collect musi riesit po svojom
        self::getSaveHandler()->setLifetime($ttl);

        // citaj vyssi popis pri rememberMe
        if ($ttl > 0) {
            $_SESSION[self::$_nsSys]['cookie_lifetime'] = $ttl;
        } else {
            unset($_SESSION[self::$_nsSys]['cookie_lifetime']);
        }

        // nizsia setCookie pozera na session_get_cookie_params
        ini_set("session.cookie_lifetime", $ttl);
        self::setCookie();
    }

    /**
     * Posle cookie. Bud surovo, alebo via response ak bude providnuty.
     */
    public static function setCookie()
    {
        // ak mame v sessione info, tak ho uplatnime... toto je riesenie
        // problemu "B" popisaneho v rememberMe vyssie
        if (isset($_SESSION[self::$_nsSys]['cookie_lifetime'])) {
            ini_set("session.cookie_lifetime", (int) $_SESSION[self::$_nsSys]['cookie_lifetime']);
        }

        $name   = self::getName();
        $value  = self::getId();
        $params = session_get_cookie_params();

        // ak mame response tak posielame vzdy
        if ($response = self::getResponse()) {
            if ($params['lifetime'] < 0) {
                $value = null; // @see Response
            }
            $response->setCookie($name, $value, $params);
        }
        // inak iba ak nie sme mock
        else if (!self::$_mock) {
            if ($params['lifetime'] < 0) {
                $params['expires'] = time() - (60*60*24*30);
                $value = '';
            } else if ($params['lifetime'] == 0) {
                $params['expires'] = 0;
            } else {
                $params['expires'] = $params['lifetime'] + time();
            }
            setcookie(
                $name, (string) $value,
                $params['expires'], $params['path'], $params['domain'],
                (bool) $params['secure'], (bool) $params['httponly']
            );
        }
    }

    /**
     * @param string|null $ns
     * @return \ArrayObject
     */
    public static function getNamespace($ns = 'default')
    {
        self::start();
        $rootNs = self::$_nsUsr;

        if (!isset($_SESSION[$rootNs])) {
            $_SESSION[$rootNs] = new \ArrayObject(
                array(), \ArrayObject::ARRAY_AS_PROPS
            );
        }
        $rootContainer = $_SESSION[$rootNs];

        // special case - ak ns je null tak vraciame root container
        if (null === $ns) {
            return $rootContainer;
        }

        if (!isset($rootContainer[$ns])) {
            if (!isset($rootContainer[$ns])) {
                $rootContainer[$ns] = new \ArrayObject(
                    array(), \ArrayObject::ARRAY_AS_PROPS
                );
            }
        }

        return $rootContainer[$ns];
    }

    /**
     * For debug mostly
     * @return string
     */
    public static function getNsSys()
    {
        return self::$_nsSys;
    }

    /**
     * For debug mostly
     * @return string
     */
    public static function getNsUsr()
    {
        return self::$_nsUsr;
    }

}