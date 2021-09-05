<?php declare(strict_types=1);

namespace MM\View\Helper;
use MM\View\Exception;

class Breadcrumbs extends ContainerOfData {
	public string $screenReaderOnlyCssClassname = 'visually-hidden';
	public string $ariaLabel = 'Breadcrumbs';
	public string $navClass = 'nav-breadcrumbs';
	public string $olClass = 'breadcrumb';
	public string $liClass = 'breadcrumb-item';
	public string $liLastClass = 'active'; // last

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

// bootstrap
// <nav aria-label="breadcrumb">
// 		<ol class="breadcrumb">
// 			<li class="breadcrumb-item"><a href="#">Home</a></li>
// 			<li class="breadcrumb-item"><a href="#">Library</a></li>
// 			<li class="breadcrumb-item active" aria-current="page">Data</li>
// 		</ol>
// </nav>

//	microdata
// <ol itemscope itemtype="https://schema.org/BreadcrumbList">
// 		<li itemprop="itemListElement" itemscope  itemtype="https://schema.org/ListItem">
// 			<a itemprop="item" href="https://example.com/dresses"><span itemprop="name">Dresses</span></a>
// 			<meta itemprop="position" content="1" />
// 		</li>
// 		<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
// 			<a itemprop="item" href="https://example.com/dresses/real"><span itemprop="name">Real Dresses</span></a>
// 			<meta itemprop="position" content="2" />
// 		</li>
// </ol>

//	rdfa
// <ol vocab="https://schema.org/" typeof="BreadcrumbList">
// 		<li property="itemListElement" typeof="ListItem">
// 			<a property="item" typeof="WebPage" href="https://example.com/dresses"><span property="name">Dresses</span></a>
// 			<meta property="position" content="1">
// 		</li>
// 		<li property="itemListElement" typeof="ListItem">
// 			<a property="item" typeof="WebPage" href="https://example.com/dresses/real"><span property="name">Real Dresses</span></a>
// 			<meta property="position" content="2">
// 		</li>
// </ol>

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

		$lis = [];

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

			//
			$isLast = !isset($this->_container[$i + 1]);
			$liClass = trim(join(' ', [$this->liClass, $isLast ? $this->liLastClass : '']));

			//
			$lis[] = "\t\t" . join("\n\t\t", array_filter([
				"<li class='$liClass' itemprop='itemListElement' itemscope itemtype='http://schema.org/ListItem' property='itemListElement' typeof='ListItem' $dataProps>",
				$isLast ? '' : "\t<a itemprop='item' property='item' typeof='WebPage' href='$item[href]'>",
				($isLast ? "\t" : "\t\t") . "<span itemprop='name' property='name'>$item[label]</span>",
				$isLast ? '' : "\t</a>",
				"</li>",
			], fn ($v) => !!$v ));
		}

		return join("\n", [
			"\n<nav class='$this->navClass' aria-label='$this->ariaLabel'>",
			"\t<ol class='$this->olClass' itemscope itemtype='http://schema.org/BreadcrumbList' vocab='http://schema.org/' typeof='BreadcrumbList'>",
			join("\n", $lis),
			"\t</ol>",
			"</nav>\n",
		]);
	}
}
