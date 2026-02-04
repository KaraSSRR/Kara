<?php
final class Work{
	/**
     * Все данные $_GET
     * @var $get array 
     */
    private $get = [];
    /**
     * Параметр направления.
     * @var $get_ajax string
     */
    private $get_ajax = [];
    /**
     * Все данные $_POST
     * @var $post array
     */
    private $post = [];
	/**
     * Время, когда начался скрипт.
     * @var $post array
     */   
    private $start_time = 0;
    /**
     * Время, когда окончился скрипт.
     * @var $post array
     */
    private $end_time = 0;
	/**
     * Начинаем конструирование с классов, которые не требуют авторизации.
     * @param $get array
     * @param $post array
     */
	public function __construct($get,$post){
		$start_array = explode(" ", microtime());
        $this->start_time = $start_array[1] + $start_array[0];
        $this->get  = $get;
        $this->post = $post;
	}
	
}