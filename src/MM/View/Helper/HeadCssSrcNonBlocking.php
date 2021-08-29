<?php

namespace MM\View\Helper;

class HeadCssSrcNonBlocking extends HeadCssSrc {
	/**
	 * @return string
	 */
	public function toString() {
		$out = '';
		foreach ($this->_container as $src) {
			$out .= "<link href='$src' rel='stylesheet' media='foo' onload=\"if (media!='all') media='all'\">\n";
		}
		return $out;
	}
}
