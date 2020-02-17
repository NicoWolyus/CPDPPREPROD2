<?php
class Customer extends CustomerCore {
	/*
    * module: adelyaapi
    * date: 2020-01-31 15:13:19
    * version: 1.3.0
    */
    public $fid_program_membership = false;
	/*
    * module: adelyaapi
    * date: 2020-01-31 15:13:19
    * version: 1.3.0
    */
    public $fid_program_membership_date;
	/*
    * module: adelyaapi
    * date: 2020-01-31 15:13:19
    * version: 1.3.0
    */
    public function __construct($id = null) {
		self::$definition['fields']['fid_program_membership'] = [
			'type' => self::TYPE_BOOL,
			'required' => false
		];
		self::$definition['fields']['fid_program_membership_date'] = [
			'type' => self::TYPE_DATE,
			'required' => false
		];
		parent::__construct($id);
	}
}