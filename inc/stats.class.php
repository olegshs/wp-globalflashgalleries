<?php

class flgalleryStats
{
	var
		$time,
		$queries,
		$startTime,
		$startQueries,
		$endTime,
		$endQueries,
		$maxExecutionTime;

	function flgalleryStats()
	{
		$this->maxExecutionTime = (int)ini_get('max_execution_time');
	}

	function microtime()
	{
		$mtime = explode( ' ', microtime() );
		return $mtime[1] + $mtime[0];
	}

	function start()
	{
		global $wpdb;

		$this->startTime = $this->microtime();
		$this->startQueries = $wpdb->num_queries;
	}

	function stop()
	{
		global $wpdb;

		$this->endQueries = $wpdb->num_queries;
		$this->endTime = $this->microtime();

		$this->time = $this->endTime - $this->startTime;
		$this->queries = $this->endQueries - $this->startQueries;
	}

	function remains()
	{
		global $timestart;
		return $timestart + $this->maxExecutionTime - $this->microtime();
	}

	function deadline()
	{
		return $this->remains() < $this->maxExecutionTime / 3;
	}

}
