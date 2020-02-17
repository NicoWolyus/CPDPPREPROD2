<?php
class Customer extends CustomerCore {
	//add new field on customer class
	public $fid_program_membership = false;
	public $fid_program_membership_date;

	public function __construct($id = null) {
//Définition du nouveau champ professionnal_id
		self::$definition['fields']['fid_program_membership'] = [
			'type' => self::TYPE_BOOL,
			'required' => false
		];
		//Définition du nouveau champ justificatif
		self::$definition['fields']['fid_program_membership_date'] = [
			'type' => self::TYPE_DATE,
			'required' => false
		];
		parent::__construct($id);
	}
}