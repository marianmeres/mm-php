<?php declare(strict_types=1);

namespace MM\View\Helper;
use MM\View\Exception;

class Breadcrumbs extends ContainerOfData {
	public string $screenReaderOnlyCssClassname = 'visually-hidden';
	public string $ariaTitleContent = 'Breadcrumb navigation';
	public string $customCssClass = '';

	protected function _validateAndNormalizeData(array $data): array {
		if (!is_array($data) || empty($data['label']) || !isset($data['href'])) {
			throw new Exception("Expecting data as ['label'=>'...', 'href' => '...']");
		}
		return $data;
	}

	public function removeDuplicateEntries(): static {
		$hrefsFound = [];
		foreach ($this->_container as $k => $data) {
			if (!isset($hrefsFound[$data['href']])) {
				$hrefsFound[$data['href']] = 1;
			} else {
				unset($this->_container[$k]);
			}
		}
		return $this;
	}

	/**
	 * To be overriden if needed. Produces aria, and both rdfa and microdata (hopefully)
	 * correct markup by default;
	 *
	 * @see http://schema.org/BreadcrumbList
	 * @see https://developers.google.com/structured-data/breadcrumbs?rd=1
	 */
	public function toString(): string {
		$count = count($this);
		if (!$count) {
			return '';
		}

		$out = "\n<nav class='breadcrumb-navigation $this->customCssClass' aria-labelledby='breadcrumb-navigation-title'>\n";
		$out .= "  <h2 id='breadcrumb-navigation-title' class='$this->screenReaderOnlyCssClassname'>$this->ariaTitleContent</h2>\n";
		$out .=
			"  <ol itemscope itemtype='http://schema.org/BreadcrumbList' vocab='http://schema.org/' typeof='BreadcrumbList'>\n";

		for ($i = 0; $i < $count; $i++) {
			$item = $this->_container[$i];

			// podporujeme custom data props, ktore sa priradia list itemu
			$dataProps = [];
			foreach ($item as $k => $v) {
				if (preg_match('/^data-/i', $k)) {
					$dataProps[] = sprintf("$k='%s'", htmlspecialchars($v));
				}
			}
			$dataProps = implode(' ', $dataProps);

			$li = "    <li itemprop='itemListElement' itemscope itemtype='http://schema.org/ListItem' property='itemListElement' typeof='ListItem' $dataProps>\n";
			$itemContent = "<span itemprop='name' property='name'>$item[label]</span>";

			if (isset($this->_container[$i + 1])) {
				$li .= "      <a itemprop='item' property='item' typeof='WebPage' href='$item[href]'>\n        $itemContent\n      </a>\n";
			} else {
				$li .= "      $itemContent\n";
			}

			$li .= sprintf(
				"      <meta itemprop='position' property='position' content='%d'>\n",
				$i + 1,
			);
			$li .= "    </li>\n";
			$out .= $li;
		}

		$out .= "  </ol>\n";
		$out .= "</nav>\n";

		return $out;
	}
}
