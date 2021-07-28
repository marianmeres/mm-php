<?php
/**
 * @author Marian Meres
 */
namespace MM\View\Helper;
use MM\View\Exception;

/**
 * Class HeadCss
 * @package MM\View\Helper
 */
class LinkRel extends ContainerOfData
{
	/**
	 * @param $data
	 * @return mixed
	 * @throws Exception
	 */
	protected function _validateAndNormalizeData($data)
	{
		if (!is_array($data) || empty($data['rel']) || !isset($data['href'])) {
			throw new Exception(
				"Expecting minimum data as ['link'=>'...', 'href' => '...']"
			);
		}
		ksort($data);
		return $data;
	}

	/**
	 * @return $this
	 */
	public function removeDuplicateEntries()
	{
		$found = [];
		foreach ($this->_container as $k => $data) {
			// $data is ksorted here
			$hash = md5(serialize($data));
			if (!isset($found[$hash])) {
				$found[$hash] = 1;
			} else {
				unset($this->_container[$k]);
			}
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function toString()
	{
		$count = count($this);
		if (!$count) {
			return '';
		}

		$this->removeDuplicateEntries();

		$out = '';

		foreach ($this->_container as $data) {
			$out .= '<link';

			// put rel first just for humans
			if (isset($data['rel'])) {
				$out .= " rel='$data[rel]'";
				unset($data['rel']);
			}

			foreach ($data as $k => $v) {
				$out .= " $k='$v'";
			}
			$out .= " />\n";
		}

		return $out;
	}
}
