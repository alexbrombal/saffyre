<?php

class Pagination {

	protected $total;
	protected $page = 1;
	protected $count;

	public function __construct($total, $page, $count) {
		$this->total = $total;
		$this->count = (int)$count;
		$this->page = max(1, $total === null ? (int)$page : min($this->pageCount(), (int)$page));
	}

	public function total($total = null) {
		if($total === null) return $this->total;
		else $this->total = (int)$total;
		$this->page = max(1, min($this->pageCount(), (int)$this->page));
	}

	public function page() {
		return max(1, min($this->pageCount(), $this->page));
	}

	public function count() {
		return $this->count;
	}
	
	public function pages() {
		$pages = array();
		for($i = 1; $i <= $this->pageCount(); $i++) {
			$pages[] = $i;
		}
		return $pages;
	}

	public function pageCount() {
		if(!$this->count()) return 0;
		if($this->total === null) return $this->page;
		return ceil($this->total() / $this->count());
	}
	
	public function start() {
		return max(1, (($this->page() - 1) * $this->count()) + 1);
	}
	
	public function end() {
		return min($this->total(), $this->page() * $this->count());
	}
	
	public function SQLStart() {
		return ($this->page() - 1) * $this->count();
	}
	
	public function SQLLimit() {
		return $this->count();
	}
	
	public function SQL() {
		return "LIMIT {$this->SQLStart()}, {$this->SQLLimit()}";
	}
	
	public function pageSummary() {
		$count = $this->pageCount();
		$current = $this->page();
		$prev = $current - 1 >= 1 ? $current - 1 : null;
		$next = $current + 1 <= $count ? $current + 1 : null;
		if($current <= 4) {
			if($count >= 5)
				$pages = array(2 => 2, 3 => 3, 4 => 4, 5 => 5);
			else {
				$pages = array();
				for($i = 2; $i <= $count; $i++)
					$pages[$i] = $i;
			}
		}
		elseif($current >= $count - 3)
			$pages = array($count - 4 => $count - 4, $count - 3 => $count - 3, $count - 2 => $count - 2, $count - 1 => $count - 1);
		elseif($next < $count) {
			$pages = array($current => $current);
			if($prev) $pages = array($prev => $prev) + $pages;
			if($next) $pages = $pages + array($next => $next);
		}
		if(reset($pages) - 2 > 1) $pages = array(reset($pages) - 2 => '...') + $pages;
		if($count - end($pages) > 2) $pages = $pages + array(end($pages) + 2 => '...');
		return array(1 => 1) + $pages + array($count => $count);
	}

}