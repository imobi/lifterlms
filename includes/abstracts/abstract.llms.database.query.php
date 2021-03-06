<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

abstract class LLMS_Database_Query {

	/**
	 * Identify the extending query
	 * @var  string
	 */
	protected $id = 'database';

	/**
	 * Arguments
	 * Original merged into defaults
	 * @var  array
	 */
	protected $arguments = array();

	/**
	 * Default arguments before merging with original
	 * @var  array
	 */
	protected $arguments_default = array();

	/**
	 * Original args before merging with defaults
	 * @var  array
	 */
	protected $arguments_original = array();

	/**
	 * Total number of results matching query parameters
	 * @var  integer
	 */
	public $found_results = 0;

	/**
	 * Maximum number of pages of results
	 * based off per_page & found_results
	 * @var  integer
	 */
	public $max_pages = 0;

	/**
	 * Number of results on the current page
	 * @var  integer
	 */
	public $number_results = 0;

	/**
	 * Array of query variables
	 * @var  array
	 */
	public $query_vars = array();

	/**
	 * Array of results retrieved by the query
	 * @var  array
	 */
	public $results = array();

	/**
	 * The raw SQL query
	 * @var  string
	 */
	protected $sql = '';

	/**
	 * Constructor
	 * @param    array      $args  query arguments
	 * @since    ??
	 * @version  ??
	 */
	public function __construct( $args = array() ) {

		$this->arguments_original = $args;
		$this->arguments_default = $this->get_default_args();

		$this->setup_args();

		$this->query();

	}

	/**
	 * Escape and add quotes to a string, useful for array mapping when building queries
	 * @param    mixed     $input  intupt data
	 * @return   string
	 * @since    ??
	 * @version  ??
	 */
	public function escape_and_quote_string( $input ) {
		return "'" . esc_sql( $input ) . "'";
	}

	/**
	 * Retrieve a query variable with an optional fallback / default
	 * @param    string     $key      variable key
	 * @param    mixed      $default  default value
	 * @return   mixed
	 * @since    ??
	 * @version  ??
	 */
	public function get( $key, $default = '' ) {

		if ( isset( $this->query_vars[ $key ] ) ) {
			return $this->query_vars[ $key ];
		}

		return $default;
	}

	/**
	 * Retrieve default arguments for a the query
	 * @return   array
	 * @since    ??
	 * @version  ??
	 */
	protected function get_default_args() {

		$args = array(
			'page' => 1,
			'per_page' => 25,
			'search' => '',
			'sort' => array(
				'id' => 'ASC',
			),
			'suppress_filters' => false,
		);

		if ( $this->get( 'suppress_filters' ) ) {
			return $args;
		}

		return apply_filters( 'llms_db_query_get_default_args', $args );

	}

	/**
	 * Get a string used as filter names unique to the extending query
	 * @param    string     $filter  filter name
	 * @return   string
	 * @since    ??
	 * @version  ??
	 */
	protected function get_filter( $filter ) {
		return 'llms_' . $this->id . '_query_' . $filter;
	}

	/**
	 * Retrieve an array of results for the given query
	 * @return   array
	 * @since    ??
	 * @version  ??
	 */
	public function get_results() {

		if ( $this->get( 'suppress_filters' ) ) {
			return $this->results;
		}

		return apply_filters( $this->get_filter( 'get_results' ), $this->results );

	}

	/**
	 * Get the number of results to skip for the query
	 * based on the current page and per_page vars
	 * @return   int
	 * @since    ??
	 * @version  ??
	 */
	protected function get_skip() {
		return absint( ( $this->get( 'page' ) - 1 ) * $this->get( 'per_page' ) );
	}

	/**
	 * Determine if we're on the first page of results
	 * @return   boolean
	 * @since    ??
	 * @version  ??
	 */
	public function is_first_page() {
		return ( 1 === $this->get( 'page' ) );
	}

	/**
	 * Determine if we're on the last page of results
	 * @return   boolean
	 * @since    ??
	 * @version  ??
	 */
	public function is_last_page() {
		return ( $this->get( 'page' ) === $this->max_pages );
	}

	/**
	 * Parse arguments needed for the query
	 * @return   void
	 * @since    ??
	 * @version  ??
	 */
	abstract protected function parse_args();

	/**
	 * Prepare the SQL for the query
	 * @return   void
	 * @since    ??
	 * @version  ??
	 */
	abstract protected function preprare_query();

	/**
	 * Execute a query
	 * @return   void
	 * @since    ??
	 * @version  ??
	 */
	public function query() {

		global $wpdb;

		$this->sql = $this->preprare_query();
		if ( ! $this->get( 'suppress_filters' ) ) {
			$this->sql = apply_filters( $this->get_filter( 'prepare_query' ), $this->sql, $this );
		}

		$this->results = $wpdb->get_results( $this->sql );
		$this->number_results = count( $this->results );

		$this->set_found_results();

	}

	/**
	 * Sets a query variable
	 * @param    string     $key  variable key
	 * @param    mixed      $val  variable value
	 * @return   void
	 * @since    ??
	 * @version  ??
	 */
	public function set( $key, $val ) {
		$this->query_vars[ $key ] = $val;
	}

	/**
	 * Set variables related to total number of results and pages possible
	 * with supplied arguments
	 * @return   void
	 * @since    ??
	 * @version  ??
	 */
	protected function set_found_results() {

		global $wpdb;

		// if no results bail early b/c no reason to calculate anything
		if ( ! $this->number_results ) {
			return;
		}

		$this->found_results = absint( $wpdb->get_var( 'SELECT FOUND_ROWS()' ) );
		$this->max_pages = absint( ceil( $this->found_results / $this->get( 'per_page' ) ) );

	}

	/**
	 * Setup arguments prior to a query
	 * @return   void
	 * @since    ??
	 * @version  ??
	 */
	protected function setup_args() {

		$this->arguments = wp_parse_args( $this->arguments_original, $this->arguments_default );

		$this->parse_args();

		foreach ( $this->arguments as $arg => $val ) {

			$this->set( $arg, $val );

		}

	}

	/**
	 * Retrieve the prepared SQL for the ORDER clase
	 * @return   string
	 * @since    ??
	 * @version  ??
	 */
	protected function sql_orderby() {

		$sql = 'ORDER BY';

		$comma = false;

		foreach ( $this->get( 'sort' ) as $orderby => $order ) {
			$pre = ( $comma ) ? ', ' : ' ';
			$sql .= $pre . "{$orderby} {$order}";
			$comma = true;
		}

		if ( $this->get( 'suppress_filters' ) ) {
			return $sql;
		}

		return apply_filters( $this->get_filter( 'orderby' ), $sql, $this );

	}

}
