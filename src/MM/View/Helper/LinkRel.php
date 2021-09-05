<?php declare(strict_types=1);

namespace MM\View\Helper;
use MM\View\Exception;

class LinkRel extends ContainerOfData {

	protected function _validateAndNormalizeData(array $data): array {
		if (!is_array($data) || empty($data['rel']) || !isset($data['href'])) {
			throw new Exception(
				"Expecting minimum data as ['href'=>'...', 'rel' => '...']",
			);
		}
		ksort($data);
		return $data;
	}

	public function removeDuplicateEntries(): static {
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

	public function toString(): string {
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
