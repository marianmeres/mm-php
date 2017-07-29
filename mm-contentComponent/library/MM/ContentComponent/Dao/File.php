<?php
namespace MM\ContentComponent\Dao;

use MM\ContentComponent\Exception;

class File extends AbstractDao
{
    /**
     * @var string
     */
    protected $baseDir = './';

    /**
     * @var string
     */
    public $extension = 'xml';

    /**
     * @var \Closure
     */
    protected $_filenameFactory;

    /**
     * @param $dir
     * @return $this
     * @throws Exception
     */
    public function setBaseDir($dir, $strict = true)
    {
        if (!is_dir($dir)) {
            if ($strict) {
                throw new Exception("Directory '$dir' does not exist.");
            }
            mkdir($dir, 0777, true);
        }
        $this->baseDir = $dir;

        return $this;
    }

    /**
     * @return string
     */
    public function getBaseDir()
    {
        return $this->baseDir;
    }

    /**
     * @param \Closure|null $factory
     */
    public function setFilenameFactory(\Closure $factory = null)
    {
        $this->_filenameFactory = $factory;
    }

    /**
     * @param $componentId
     * @return string
     */
    public function getFilename($componentId)
    {
        // custom factory?
        if ($this->_filenameFactory) {
            return call_user_func_array($this->_filenameFactory, [
                $this, $componentId
            ]);
        }

        // default simple fallback
        return "$this->baseDir/$componentId.$this->extension";
    }

    /**
     * @param $componentId
     * @param array $options
     * @return bool
     */
    public function exists($componentId, array $options = null)
    {
        return file_exists($this->getFilename($componentId));
    }

    /**
     * @param $componentId
     * @param $data
     * @param array $options
     * @return $this
     */
    public function create($componentId, $data, array $options = null)
    {
        $filename = $this->getFilename($componentId);

        $dirname = dirname($filename);
        if (!is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }

        file_put_contents($filename, $data, \LOCK_EX);
        return $this;
    }

    /**
     * @param $componentId
     * @param array $options
     * @return string
     */
    public function read($componentId, array $options = null)
    {
        $filename = $this->getFilename($componentId);
        if (file_exists($filename)) {
            return file_get_contents($filename);
        }
        return null;
    }

    /**
     * @param $componentId
     * @param $data
     * @param array $options
     * @return $this
     */
    public function update($componentId, $data, array $options = null)
    {
        return $this->create($componentId, $data);
    }

    /**
     * @param $componentId
     * @param array $options
     * @return $this
     */
    public function delete($componentId, array $options = null)
    {
        $filename = $this->getFilename($componentId);
        if (file_exists($filename)) {
            unlink($filename);
            return true;
        }
        return false;
    }

    /**
     * @param array $options
     * @return array
     */
    public function fetchAvailableComponentIds(array $options = null)
    {
        $recursive = false;
        if (isset($options['recursive'])) {
            $recursive = (bool) $options['recursive'];
        }

        $baseDir = $this->getBaseDir();
        $out = [];

        if ($recursive) {
            $dir = new \RecursiveDirectoryIterator(
                $baseDir, \RecursiveDirectoryIterator::SKIP_DOTS
            );
            $it = new \RecursiveIteratorIterator($dir);
        } else {
            $it = new \DirectoryIterator($baseDir);
        }

        $e = preg_quote($this->extension);
        $baseDir = rtrim($baseDir, "/") . "/";

        foreach ($it as $f) {
            // naive: validates against extension only...
            if ($f->isFile() && preg_match('/\.' . $e . '$/i', "$f")) {
                $out[] = mb_substr(
                    $f->getPathname(), mb_strlen($baseDir), -(mb_strlen($this->extension)+1)
                );
            }
        }

        if (!empty($options['sort_fn']) && $options['sort_fn'] instanceof \Closure) {
            usort($out, $options['sort_fn']);
        } else {
            sort($out);
        }

        return $out;
    }
}