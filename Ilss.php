<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
class Ilss extends CI_Model {
	protected $no_data;
	public $year;
	public $account_type, $page_data;
	function __construct() {
		parent::__construct();
		
		if ($this->migration->current() === FALSE) {
			show_error($this->migration->error_string());
		}
		
		
		$this->load->model('http/host');
	
		$this->load->dbutil();
		$this->account_type = $this->session->userdata('account_type');
		$this->account_id    = $this->session->userdata('account_id');
		$this->year          = $this->settings->running_year();
		$this->no_data       = json_decode(json_encode(array()));
		$this->upload_path    = $this->session->userdata('upload_path');
		$general_setings = $this->settings->general_settings();
			//adding variables to this class
			foreach($general_setings as $key => $val){
				$this->page_data[$key] = $val;
				$this->$key = $val;
			}
		
		
		
		
		$directories = [ 	'exports', //exports
							'uploads', //ordinary uploads
							'profiles', //profiles  
							'syllabus', //academic syllabus
							'documents', //documents, 
							'logo', //system logo
							'images' //profile images
		];
		foreach ($directories as $dir) {
			$dir = 'uploads/' .$this->upload_path .'/'. $dir . '/';
			if ( ! is_dir($dir) ){
				mkdir($dir, 0777, true);
			}
		}
		
		$options = array(
			'format' => 'txt',
			'add_drop' => TRUE,
			'add_insert' => TRUE,
			'newline' => "\n"
		);
		
		$backup  = $this->dbutil->backup($options);
		$this->load->helper('file');
		write_file(APPPATH .'storage/backup/'. $this->host->database_prefix() . $this->upload_path . '.sql', $backup);
		
		//$account_type = explode('/', $this->session->userdata('account_type'));
		//$this->account_type = $account_type[1];
	}
	function system_settings($param1 = '', $param2 = '', $param3 = '') {
		
		if ($param1 == 'update_settings') {
			
				$this->db->where('settings_id', 'general');
				$this->db->update('settings', $this->input->post());
			
			$this->session->set_flashdata('flash_message', translate('data_updated'));
			redirect(base_url($this->account_url) . '/system_settings', 'refresh');
		} else if ($param1 == 'upload_logo') {
			$dir = 'uploads/'. $this->upload_path .'/logo';
			move_uploaded_file($_FILES[ 'userfile' ][ 'tmp_name' ], $dir . '/logo.png');
			$this->session->set_flashdata('flash_message', translate('logo_updated'));
			redirect(base_url($this->account_url) . '/system_settings', 'refresh');
		} else if ($param1 == 'change_theme') {
			$data[ 'system_theme' ] = $param2;
			$this->db->where('settings_id', 'general');
			$this->db->update('settings', $data);
			$this->session->set_flashdata('flash_message', translate('theme_selected'));
			redirect(base_url($this->account_url) . '/system_settings', 'refresh');
		} else {
			$this->page_data[ 'page_name' ]  = 'system_settings';
			$this->page_data[ 'subdir' ]     = 'settings';
			$this->page_data[ 'page_title' ] = translate('system_settings');
			$this->page_data[ 'settings' ]   = $this->db->get('settings')->result_array();
			$this->load->view('body', $this->page_data);
		}
	}
	function clear_cache() {
		$this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		$this->output->set_header('Pragma: no-cache');
	}
	function class_name($class_id = '') {
		if ($class_id != '') {
			return $this->db->get_where('class', array(
				'class_id' => $class_id
			))->row()->name;
		}
		return '';
	}
	function exam_name($exam_id = '') {
		if ($exam_id != '') {
			return $this->db->get_where('exam', array(
				'exam_id' => $exam_id
			))->row()->name;
		}
		return '';
	}
	function subject_name($subject_id = '') {
		if ($subject_id != '') {
			return $this->db->get_where('subject', array(
				'subject_id' => $subject_id
			))->row()->name;
		}
		return '';
	}
	function teacher_name($account_id = '') {
		if ($account_id != '') {
			$query = $this->db->get_where('account', array(
				'account_id' => $account_id
			));
			if ($query->num_rows() > 0) {
				return $query->row()->name;
			} else {
				return '';
			}
		}
		return '';
	}
	function section_name($section_id = array()) {
		if (is_array($section_id) AND !empty($section_id)) {
			foreach ($section_id as $row) {
				$query = $this->db->get_where('section', array(
					'section_id' => $row
				));
				if ($query->num_rows() > 0) {
					$names[ ] = $query->row()->name;
				}
			}
			return implode(', ', $names);
		} else if ($section_id != '' AND !is_array($section_id)) {
			$query = $this->db->get_where('section', array(
				'section_id' => $section_id
			));
			if ($query->num_rows() > 0) {
				return $query->row()->name;
			}
		} else {
			return '';
		}
	}
	function student_name($account_id = '') {
		if ($account_id != '') {
			$query = $this->db->get_where('account', array(
				'account_id' => $account_id
			));
			if ($query->num_rows() > 0) {
				return $query->row()->name;
			} else {
				return '';
			}
		} else {
			return '';
		}
	}
	function guardian_name($account_id = '') {
		if ($account_id != '') {
			$query = $this->db->get_where('account', array(
				'account_id' => $account_id
			));
			if ($query->num_rows() > 0) {
				return $query->row()->name;
			} else {
				return '';
			}
		} else {
			return '';
		}
	}
	function section_info($section_id = '') {
		if ($section_id != '') {
			$query = $this->db->get_where('section', array(
				'section_id' => $section_id
			));
			if ($query->num_rows() > 0) {
				return $query->row();
			}
		}
	}
	function class_info($class_id = '') {
		if ($class_id != '') {
			$query = $this->db->get_where('class', array(
				'class_id' => $class_id
			));
			if ($query->num_rows() > 0) {
				return $query->row();
			}
		}
	}
	
	function enrol_info($account_id = '', $year = '') {
		if ($account_id != '') {
			if ($year != '') {
				$year = $year;
			} else {
				$year = $this->year;
			}
			$query = $this->db->get_where('enrol', array(
				'account_id' => $account_id,
				'year' => $year
			));
			if ($query->num_rows() > 0) {
				return $query->row();
			}
		}
	}
	
	function exam_info($exam_id = '') {
		if ($exam_id != '') {
			$query = $this->db->get_where('exam', array(
				'exam_id' => $exam_id
			));
			if ($query->num_rows() > 0) {
				return $query->row();
			}
		}
	}
	function grade_info($grade_id = '') {
		if ($grade_id != '') {
			$query = $this->db->get_where('grade', array(
				'grade_id' => $grade_id
			));
			if ($query->num_rows() > 0) {
				return $query->row();
			}
		}
	}
	function subject_info($subject_id = '') {
		if ($subject_id != '') {
			$query = $this->db->get_where('subject', array(
				'subject_id' => $subject_id
			));
			if ($query->num_rows() > 0) {
				return $query->row();
			}
		}
	}
	function admin_info($admin_id = '') {
		if ($admin_id != '') {
			$query = $this->db->get_where('account', array(
				'account_id' => $admin_id
			));
			if ($query->num_rows() > 0) {
				return $query->row();
			}
		}
	}
	function material_info($material_id = '') {
		if ($material_id != '') {
			$query = $this->db->get_where('material', array(
				'material_id' => $material_id
			));
			if ($query->num_rows() > 0) {
				return $query->row();
			}
		}
	}
	function get_enrol($class_id = '', $section_id = array(), $year = '') {
		$data['status'] = 1;
		if ($class_id == '') {
			if ($year != '') {
				$data['year'] = $year;
			} else {
				$data['year'] = $this->year;
			}
			return $this->db->get_where('enrol', $data)->result();
		} else if ($class_id != '') {
			$data['class_id'] = $class_id;
			if ($year != '') {
				$data['year'] = $year;
			} else {
				$data['year'] = $this->year;
			}
			if (is_array($section_id) AND count($section_id) > 0) {
				$this->db->where_in('section_id', $section_id);
			}
			if (!is_array($section_id) AND $section_id != '') {
				$data['section_id'] = $section_id;
			}
			$students = $this->db->get_where('enrol', $data)->result();
			return $students;
		}
		//var_dump($students);
	}
	
	function get_students(){
		foreach($this->get_enrol() as $student){
			$student = $this->account_info($student->account_id);
			$students[] = [	'account_id' => $student->account_id,
							'name' => $student->name, 
							'surname' => $student->surname, 
							'phone' => $student->phone, 
							'email' => $student->email, 
							'guardian_id' => $student->guardian_id
							];
		}
		return json_decode(json_encode($students));
	}
	
	function account_info($account_id = ''){
		
		$result = '';
		if ($account_id != '' && ! is_null($account_id) && $account_id != '0') {
			$account = $this->db->get_where('account', array('account_id' => $account_id));
			if($account->num_rows() > 0){
				$result = $account->row();
			}
		}
		return $result;
		
	}
	function teacher_info($account_id = '') {
		return $this->account_info($account_id);
	}
	function guardian_info($account_id = '') {
		return $this->account_info($account_id);
	}
	function student_info($account_id = '') {
		return $this->account_info($account_id);
	}
	function get_sex($account_id = '') {
		$sex = $this->account_info($account_id)->sex;
			return $sex == 1 ? 'M' : 'F';
	}
	function get_birthdays() {
		$birthdays = $this->db->select('birthday, name, surname')->from('account')->get();
		if ($birthdays->num_rows() > 0) {
			foreach ($birthdays->result() as $birthday) {
				$array[ ] = $birthday;
			}
			return $array;
		} else {
			return array();
		}
	}
	function get_classes() {
		return $this->db->get('class')->result();
	}
	function get_sections($class_id = '') {
		if ($class_id != '') {
			$query = $this->db->get_where('section', array(
				'class_id' => $class_id
			));
			if ($query->num_rows() > 0) {
				return $query->result();
			}
		} else {
			return $this->db->get('section')->result();
		}
	}
	function get_subjects($class_id = '') {
		if ($class_id != '') {
			$query = $this->db->get_where('subject', array(
				'class_id' => $class_id
			));
			
			return $query->result();
			
		} else {
			return $this->db->get_where('subject', array(
				'year' => $this->year
			))->result();
		}
	}
	function get_tasks($class_id = '') {
		if ($class_id != '') {
			$query = $this->db->get_where('exam', array(
				'class_id' => $class_id,
				'year' => $this->year
			));
			if ($query->num_rows() > 0) {
				return $query->result();
			}
		} else {
			return $this->db->get_where('exam', array(
				'year' => $this->year
			))->result();
		}
	}
	function get_subject_tasks($subject_id = '') {
		if ($subject_id != '') {
			$query = $this->db->get_where('exam', array(
				'subject_id' => $subject_id,
				'year' => $this->year
			));
			if ($query->num_rows() > 0) {
				return $query->result();
			}
		} else {
			return $this->no_data;
		}
	}
	function academic_history($account_id = '', $year = '') {
		if ($account_id != '') {
			if ($year != '') {
				$this->db->where('year', $year);
			}
			$this->db->where_in('account_id', $account_id);
			$this->db->order_by('year ASC, term ASC');
			$query = $this->db->get('mark');
			if ($query->num_rows() > 0) {
				return $query->result();
			} else {
				return '';
			}
		}
		if ($account_id == '') {
			return '';
		}
	}
	function order_by($order_by) {
		return $this->db->order_by($order_by);
	}
	function get_grades() {
		return $this->db->get('grade')->result();
	}
	function get_teachers() {
		$this->db->where('account_type', 'teacher');
		return $this->db->get('account')->result();
	}
	function get_guardians() {
		$current_students = $this->get_enrol(); //current year students
		$parents = [];
		foreach($current_students as $student){
			$acc_info = $this->account_info($student->account_id);
			if($acc_info->guardian_id != '' && $acc_info->guardian_id > 0){
				$parents[] = json_decode($this->account_info($acc_info->guardian_id), true);
			}else{
				continue;
			}
		}
		return json_decode(json_encode($parents));
	}
	function settings($value = '') {
		if ($value != '') {
			return $this->settings->system_settings($value);
		} else {
			return $this->settings->system_settings();
		}
	}
	function profile_info($account_type = null) {
		return $this->db->get_where('account', array('account_id' => $this->session->userdata('account_id')))->row();
	}
	function birthday($date = '') {
		if (is_string($date)) {
			return date('Y-m-d', $date);
		} else {
			return '';
		}
	}
	function create_account($action = 'add'){
		$register = FALSE; //set registration to false
		$account_type = $this->input->post('account_type');
		$privilages = array_values($this->registeraccount_types());
		$data['account_type'] = in_array($account_type, $privilages) ? $account_type : 'general';
		
		//other accounts data
		if(	$account_type == 'teacher' || 
			$account_type == 'guardian' || 
			$account_type == 'employee' ||
			$account_type == 'admin' ||
			$account_type == 'manager' ||
			$account_type == 'accountant'||
			$account_type == 'librarian' ||
			$account_type == 'principal'
			){ //unique data
				$data['persal_number'] = $this->input->post('persal_number');
				$data['proffession'] = $this->input->post('proffession');
				$data['union'] = $this->input->post('union');
		}
		//student account data
		if($account_type == 'student'){
			$data['guardian_id'] = $this->input->post('account_id'); //guardian account ID
		}
		
		//common data
		$data['name'] 				= ucwords($this->input->post('name'));
		$data['surname'] 			= ucwords($this->input->post('surname'));
		$data['sex'] 				= $this->input->post('sex');
		$data['birthday'] 			= strtotime($this->input->post('birthday'));
		$data['id_number'] 			= $this->input->post('id_number');
		$data['race'] 				= strtolower($this->input->post('race'));
		$data['phone'] 				= $this->decode_phone($this->input->post('phone'));
		$data['home'] 				= $this->decode_phone($this->input->post('home'));
		$data['email'] 				= strtolower($this->input->post('email'));
		$data['password'] 			= $this->get_password($data['id_number']); //auto create password
		$data['address'] 			= $this->input->post('address');
		$data['postal_code'] 		= $this->input->post('postal_code');
		$data['home_language'] 		= strtolower($this->input->post('home_language'));
		$data['disability'] 		= $this->input->post('disability');
		$data['date_added'] 		= now();
		$data['title'] 				= ucfirst($this->input->post('title'));
		$data['initials'] 			= strtoupper($this->input->post('initials'));
		
		/*
		* checks whether exists
		*/
		$validate['email'] 		= $data['email'];
		$validate['id_number'] 	= $data['id_number'];
		$validate['phone'] 		= $data['phone'];
		$register = $this->_register($validate);
		if($register){
			$this->__complete_registration($data, $account_type);
		}
	}
	/*
	* it finalises account registration 
	*/
	function __complete_registration($data, $account_type){
		$data['account_type'] = $account_type;
		$this->db->insert('account', $data);
		if($account_type == 'student'){
			$account_id = $this->db->insert_id();
			$this->_enrol_student($account_id);
		}
	}
	/*
	* This checks whether user exists
	*/
	function _register($validate) {
		$register = FALSE;
		if (is_array($validate) && count($validate) > 0) {
			$query1 = $this->db->get_where('account', array('email' 	=> $validate['email']))->num_rows();
			$query2 = $this->db->get_where('account', array('phone' 	=> $validate['phone']))->num_rows();
			$query3 = $this->db->get_where('account', array('id_number' => $validate['id_number']))->num_rows();
			
			if($query1 < 1 && $query2 < 1 && $query3 < 1){
				$register = TRUE;
			}
		}
		return $register;
	}
	/*
	*register as student 
	*/
	private function _enrol_student($account_id) {
		
		$dir        = 'uploads/' . $this->upload_path . '/images';
		if (count($_FILES) > 0) {
			move_uploaded_file($_FILES['userfile']['tmp_name'], $dir . '/' . $account_id . '.jpg');
		}
		$running_year = explode('-', $this->year);
		$running_year = $running_year[ 0 ];
		if ($this->db->get_where('enrol', array(
			'year' => $this->year
		))->num_rows() > 0) {
			$this->db->select_max('student_number');
			$no     = $this->db->get_where('enrol', array(
				'year' => $this->year
			))->row()->student_number;
			$number = $no + 1;
		} else {
			$number = $running_year . '00001';
		}
		$enrol_data['account_id']     = (int) $account_id;
		$enrol_data['student_number'] = $number;
		$enrol_data['class_id']       = (int) $this->input->post('class_id');
		$enrol_data['section_id']     = (int) $this->input->post('section_id');
		$enrol_data['status']         = 1;
		$enrol_data['date_added']     = now();
		$enrol_data['year']           = $this->year;
		$this->db->insert('enrol', $enrol_data);
	}
	function _enrol_single_student(){
		$data[ 'name' ]        = ucwords(strtolower(str_replace(' ', '_', $this->input->post('name'))));
		$data[ 'surname' ]     = ucwords(strtolower($this->input->post('surname')));
		$data[ 'id_number' ]   = $this->input->post('id_number');
		$birthday 			   = str_replace('/', '-', $this->input->post('birthday'));
		$data[ 'birthday' ]    = strtotime($birthday);
		$data[ 'email' ]       = strtolower($this->input->post('email'));
		$data[ 'password' ]    = $this->get_password($this->input->post('id_number'));
		$data[ 'phone' ]       = $this->decode_phone($this->input->post('phone'));
		$data[ 'home' ]        = $data[ 'phone' ];
		$data[ 'address' ]     = ucwords(strtolower($this->input->post('address')));
		$data[ 'postal_code' ] = $this->input->post('postal_code');
		$data[ 'guardian_id' ] = $this->input->post('guardian_id');
		$data[ 'sex' ] 		   = $this->input->post('sex');
		$register           = $this->_register($data);
		if ($register) {
			$this->__complete_registration($data, 'student');
			$this->session->set_flashdata('flash_message', translate('registration_successful'));
			$this->emailer->welcome_email('student', $data[ 'email' ]);
			$this->sms->welcome_sms('student', $data[ 'phone' ]);
		}
	}
	function _update_single_student(){
		$data[ 'name' ]        = ucwords(strtolower(str_replace(' ', '_', $this->input->post('name'))));
		$data[ 'surname' ]     = ucwords(strtolower($this->input->post('surname')));
		$data[ 'id_number' ]   = $this->input->post('id_number');
		$birthday 			   = str_replace('/', '-', $this->input->post('birthday'));
		$data[ 'birthday' ]    = strtotime($birthday);
		$data[ 'email' ]       = strtolower($this->input->post('email'));
		$data[ 'password' ]    = $this->get_password($this->input->post('id_number'));
		$data[ 'phone' ]       = $this->decode_phone($this->input->post('phone'));
		$data[ 'home' ]        = $data[ 'phone' ];
		$data[ 'address' ]     = ucwords(strtolower($this->input->post('address')));
		$data[ 'postal_code' ] = $this->input->post('postal_code');
		$data[ 'guardian_id' ] = $this->input->post('guardian_id');
		$data[ 'sex' ] 		   = $this->input->post('sex');
		$data[ 'guardian_id' ] = $this->input->post('guardian_id');
		
		$this->db->where('account_id', $this->input->post('account_id'));
		$this->db->update('account', $data);
		
		$data2[ 'section_id' ] = $this->input->post('section_id');
		$data2[ 'class_id' ]   = $this->input->post('class_id');
		$data2[ 'status' ]     = 1;
		$this->db->where('account_id', $this->input->post('account_id'));
		$this->db->update('enrol', $data2);
		move_uploaded_file($_FILES[ 'userfile' ][ 'tmp_name' ], 'uploads/student_image/' . $this->upload_path . '/' . $param2 . '.jpg');
		$this->clear_cache();
		$this->session->set_flashdata('flash_message', translate('data_updated'));
	}
	function _enrol_bulk_online(){
		
		$number_of_students = sizeof($this->input->post('name'));
		$names              = $this->input->post('name');
		$surnames           = $this->input->post('surname');
		$id_numbers         = $this->input->post('id_number');
		$email              = $this->input->post('email');
		$phones             = $this->input->post('phone');
		if ($number_of_students > 0) {
			for ($count = 0; $count < $number_of_students; $count++) {
				$data[ 'name' ]        = (string) ucwords(strtolower($names[ $count ]));
				$data[ 'surname' ]     = (string) ucwords(strtolower($surnames[ $count ]));
				$data[ 'id_number' ]   = $id_numbers[ $count ];
				$data[ 'birthday' ]    = $this->get_birthday($id_numbers[ $count ]);
				$data[ 'sex' ]         = $this->get_gender($id_numbers[ $count ]);
				$data[ 'phone' ]       = $this->decode_phone($phones[ $count ]);
				$data[ 'email' ]       = strtolower($email[ $count ]);
				$data[ 'address' ]     = '';
				$data[ 'postal_code' ] = '';
				$data[ 'home' ]        = $this->decode_phone($data[ 'phone' ]);
				$data[ 'password' ]    = $this->get_password($data[ 'id_number' ]);
				$data[ 'guardian_id' ] = '';
				$register              = $this->_register($data);
				
				if ($register) {
					$this->__complete_registration($data, 'student');
					$this->session->set_flashdata('flash_message', translate('registration_successful'));
					$this->emailer->welcome_email('student', $data[ 'email' ]);
					$this->sms->welcome_sms('student', $data[ 'phone' ]);
				}
			}
		}
	}
	
	function _enrol_bulk_import(){
		$dir          = 'uploads/' . $this->upload_path . '/uploads';
		$excel_name   = substr(md5(rand(0, 5000)), 0, 10);
		$file       = $dir . '/' . $excel_name . '.xlsx';
		move_uploaded_file($_FILES[ 'userfile' ][ 'tmp_name' ], $file);
		$excel = PHPExcel_IOFactory::createReader('Excel2007')->load($file);
		$enrol = $excel->getActiveSheet()->toArray(null, true, true, true);
		
		for ($i = 1; $i <= 9; $i++) {
			unset($enrol[ $i ]);
		}
		
		foreach ($enrol as $student_entry) {
			$values = array(
				'B' => 'name',
				'C' => 'surname',
				'D' => 'id_number',
				'E' => 'email',
				'F' => 'phone'
			);
			foreach ($values as $key => $val) {
				if ($val != 'id_number') {
					$data[ $val ] = (string) ucwords(strtolower($student_entry[ $key ]));
				} else {
					$data[ $val ] = (int) str_replace(' ', '', $student_entry[ $key ]);
				}
			}
			if (count($data) <= 5) {
				$data[ 'birthday' ]    = $this->get_birthday($data[ 'id_number' ]);
				$data[ 'sex' ]         = $this->get_gender($data[ 'id_number' ]);
				$data[ 'phone' ]       = $this->decode_phone($data[ 'phone' ]);
				$data[ 'address' ]     = '';
				$data[ 'postal_code' ] = '';
				$data[ 'home' ]        = $data[ 'phone' ];
				$data[ 'password' ]    = $this->get_password($data[ 'id_number' ]);
				$data[ 'guardian_id' ] = '';
				$register              = $this->_register($data);
				if ($register) {
					$this->__complete_registration($data, 'student');
					$this->session->set_flashdata('flash_message', translate('registration_successful'));
					//$this->emailer->welcome_email('student', $data[ 'email' ]);
					//$this->sms->welcome_sms('student', $data[ 'phone' ]);
				}
			}
		}
	}
	function get_excel_document($class_id, $section_id, $subject_id, $exam_id) {
		$admin = $this->db->get_where('account', array('account_id' => $this->session->userdata('account_id')))->row();
		$this->phpexcel->getProperties()->setCreator($this->settings('system_name'))->setLastModifiedBy($this->session->userdata('name') . ' ' . $this->session->userdata('surname'))->setCompany($this->settings('system_name'))->setManager($admin->name . ' ' . $admin->surname)->setTitle($this->class_name($class_id) . ' ' . $this->section_name($section_id) . ' ' . translate('marksheet'))->setSubject('marks for ' . $this->subject_name($subject_id))->setDescription($this->subject_name($subject_id) . ' ' . $this->exam_info($exam_id)->name . ' for Term' . $this->exam_info($exam_id)->term)->setKeywords($this->subject_name($subject_id))->setCategory($this->class_name($class_id) . ' Documents')->setCustomProperty('class_id', $class_id)->setCustomProperty('section_id', $section_id)->setCustomProperty('subject_id', $subject_id)->setCustomProperty('exam_id', $exam_id)->setCustomProperty('year', $this->year);
		$check_exam = $this->db->get_where('mark', array(
			'exam_id' => $exam_id,
			'class_id' => $class_id,
			'section_id' => $section_id,
			'subject_id' => $subject_id,
			'year' => $this->year
		));
		$data       = $this->db->select('account_id, student_number')->from('enrol')->where(array(
			'class_id' => $class_id,
			'section_id' => $section_id
		))->get()->result();
		$i          = 1;
		foreach ($data as $row) {
			$student                      = $this->db->select('name, surname, id_number')->from('account')->where(array(
				'account_id' => $row->account_id
			))->get()->row();
			$export_data['#']           = $i++ . '.';
			//$export_data['id']          = $student->id_number;
			$export_data['student no.'] = $row->student_number;
			$export_data["Learner's name"]        = $student->name.' '.$student->surname;
			//$export_data['surname']     = $student->surname;
			$account_id                   = $this->db->get_where('account', array(
				'id_number' => $student->id_number
			))->row()->account_id;
			$export_data['mark']        = count($check_exam) > 0 ? $this->db->get_where('mark', array(
				'exam_id' => $exam_id,
				'class_id' => $class_id,
				'section_id' => $section_id,
				'subject_id' => $subject_id,
				'year' => $this->year,
				'account_id' => $account_id
			))->row()->mark_obtained : 0;
			$arrays_container[ ]          = $export_data;
		}
		$counter                 = 0;
		$position_id_number      = 1;
		$position_student_number = 2;
		$position_name           = 3;
		$position_surname        = 4;
		$position_mark           = 5;
		
		$headers                 = array();
		foreach ($arrays_container as $an_array) {
			foreach ($an_array as $key => $val) {
				if (!in_array($key, $headers)) {
					$headers[ ] = $key;
				}
			}
		}
		$alphabets       = strtoupper('abcdefghijklmnopqrstuvwxyz');
		$alphabets_array = str_split($alphabets);
		$a               = $alphabets_array;
		$h               = $headers;
		$column          = array_intersect_key($a, $h);
		$post_headers    = array_intersect_key($h, $a);
		$main_start      = 1;
		$at_row          = $main_start + 1;
		foreach ($arrays_container as $an_array) {
			$col_count  = $counter;
			$id_number  = 100;
			$student_no = 1;
			$name       = 2;
			$surname    = 400;
			$mark       = 3;
			foreach ($an_array as $key => $val) {
				if ($col_count < count($column)) {
					if ($col_count == $id_number) {
						//$array['id_number'] = $val;
						//$name                 = $this->db->get_where('account', $array)->row()->name;
					} elseif ($col_count == $student_no) {
						$student_no = $val;
					} elseif ($col_count == $name) {
						$name = $val;
					} elseif ($col_count == $surname) {
						$surname = $val;
					} elseif ($col_count == $mark) {
						$mark = $val;
					}
					$this->phpexcel->getActiveSheet()->setCellValue($column[ $col_count ] . $at_row, $val);
					$xy = $column[ $col_count ] . $at_row;
					$this->phpexcel->getActiveSheet()->getStyle($column[ $col_count ] . $at_row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
					$col_count++;
					if ($col_count == (count($column))) {
						$last_cell = $column[ count($column) - 1 ] . $at_row;
						$this->phpexcel->getActiveSheet()->getStyle($last_cell)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
						$this->phpexcel->getActiveSheet()->getComment($last_cell)->setAuthor('ILSS');
						$objCommentRichText = $this->phpexcel->getActiveSheet()->getComment($last_cell)->getText()->createTextRun('');
						$objCommentRichText->getFont()->setBold(true);
						$exam_info = $this->exam_info($exam_id);
						$deadline = $exam_info->deadline;
						if (strtotime(date("Y-m-d H:i:s")) <= $deadline) {
							$this->phpexcel->getActiveSheet()->getComment($last_cell)->getText()->createTextRun("\r\n");
							$this->phpexcel->getActiveSheet()->getComment($last_cell)->getText()->createTextRun('Submit marks by ' . date('d M Y', $deadline));
							$this->phpexcel->getActiveSheet()->getComment($last_cell)->getText()->createTextRun("\r\n");
							$this->phpexcel->getActiveSheet()->getComment($last_cell)->setWidth('160pt');
							$this->phpexcel->getActiveSheet()->getComment($last_cell)->setHeight('40pt');
							$this->phpexcel->getActiveSheet()->getComment($last_cell)->setMarginLeft('150pt');
							$this->phpexcel->getActiveSheet()->getComment($last_cell)->getFillColor()->setRGB('EEEEEE');
						} else {
							$this->phpexcel->getActiveSheet()->getComment($last_cell)->getText()->createTextRun("\r\n");
							$this->phpexcel->getActiveSheet()->getComment($last_cell)->getText()->createTextRun('Deadline passed ' . date('d M Y', $deadline));
							$this->phpexcel->getActiveSheet()->getComment($last_cell)->setWidth('100pt');
							$this->phpexcel->getActiveSheet()->getComment($last_cell)->setHeight('100pt');
							$this->phpexcel->getActiveSheet()->getComment($last_cell)->setMarginLeft('150pt');
							$this->phpexcel->getActiveSheet()->getComment($last_cell)->getFillColor()->setRGB('EEEEEE');
						}
						$col_count = 0;
					}
				}
			}
			$at_row++;
		}
		$this->phpexcel->getActiveSheet()->getStyle(current($column) . $main_start . ':' . next($column) . $at_row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
		for ($i = 0; $i < count($column); $i++) {
			$this->phpexcel->getActiveSheet()->setCellValue($column[ $i ] . $main_start, strtoupper($post_headers[ $i ]));
			if ($column[ $i ] != end($column)) {
				$this->phpexcel->getActiveSheet()->getColumnDimension($column[ $i ])->setAutoSize(true);
				$this->phpexcel->getActiveSheet()->getColumnDimension($column[ $i ])->setWidth(60);
			}
			if ($column[ $i ] == end($column)) {
				$this->phpexcel->getActiveSheet()->getColumnDimension($column[ $i ])->setWidth(20);
			}
		}
		$this->phpexcel->getActiveSheet()->getStyle(reset($column) . $main_start . ':' . end($column) . $main_start)->applyFromArray(array(
			'fill' => array(
				'type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
				'rotation' => 90,
				'startcolor' => array(
					'argb' => 'FFA0A0A0'
				),
				'endcolor' => array(
					'argb' => 'FFFFFFFF'
				)
			)
		));
		$this->phpexcel->getActiveSheet()->getProtection()->setSheet(true);
		$this->phpexcel->getActiveSheet()->protectCells(reset($column) . $main_start . ':' . end($column) . $at_row, $this->account_type);
		$this->phpexcel->getSecurity()->setLockWindows(true);
		$this->phpexcel->getSecurity()->setLockStructure(true);
		$this->phpexcel->getSecurity()->setWorkbookPassword($this->account_type);
		$this->phpexcel->getActiveSheet()->getProtection()->setPassword($this->account_type);
		$this->phpexcel->getActiveSheet()->getProtection()->setSheet(true);
		$this->phpexcel->getActiveSheet()->getProtection()->setSort(true);
		$this->phpexcel->getActiveSheet()->getProtection()->setInsertRows(true);
		$this->phpexcel->getActiveSheet()->getProtection()->setFormatCells(true);
		$deadline = $this->db->get_where('exam', array(
			'exam_id' => $exam_id
		))->row()->deadline;
		if (strtotime(date("Y-m-d H:i:s")) <= $deadline) {
			$this->phpexcel->getActiveSheet()->getStyle(end($column) . $main_start . ':' . end($column) . $at_row)->getProtection()->setLocked(PHPExcel_Style_Protection::PROTECTION_UNPROTECTED);
		}
		$this->phpexcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&G&C&HThis document remain the property of ' . $this->settings('system_name'));
		$this->phpexcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&B' . $this->phpexcel->getProperties()->getTitle() . '&RPage &P of &N');
		$objDrawing = new PHPExcel_Worksheet_HeaderFooterDrawing();
		$objDrawing->setName('ILSS logo');
		$logo = 'uploads/emblem/' . $this->upload_path . '/logo.png';
		$file = is_file($logo) ? $logo : 'assets/images/logo_with_name.png';
		$objDrawing->setPath($file);
		$objDrawing->setHeight(45);
		$this->phpexcel->getActiveSheet()->getHeaderFooter()->addImage($objDrawing, PHPExcel_Worksheet_HeaderFooter::IMAGE_HEADER_LEFT);
		$this->phpexcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
		$this->phpexcel->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
		$this->phpexcel->getActiveSheet()->setTitle(strtoupper($this->subject_info($exam_info->subject_id)->acronym.'_'.$this->class_info($class_id)->name_numeric . $this->section_name($section_id) . '('.$exam_info->name. '-T'. $exam_info->term .')'));
		$this->phpexcel->setActiveSheetIndex(0);
		$objWriter = PHPExcel_IOFactory::createWriter($this->phpexcel, 'Excel2007');
		$download = 'uploads/exports/' . $this->upload_path . '/' . str_replace(' ', '', $this->class_name($class_id)) . '_' . date('dmY') . '.xlsx';
		$objWriter->save($download);
		force_download($download, null);
	}
	function get_birthday($id_number = '0000000000000') {
		$splitted_id = str_split($id_number);
		$DoB         = (int) $splitted_id[ 4 ] . $splitted_id[ 5 ];
		$MoB         = (int) $splitted_id[ 2 ] . $splitted_id[ 3 ];
		$YoB         = (int) $splitted_id[ 0 ] . $splitted_id[ 1 ];
		if ($MoB <= 12 && $DoB <= 31) {
			if ($splitted_id[ 0 ] <= 6) {
				$YoB = 2000 + $YoB;
			} elseif ($splitted_id[ 0 ] >= 7) {
				$YoB = 1900 + $YoB;
			}
			$birthday = strtotime($DoB . '-' . $MoB . '-' . $YoB);
			return $birthday;
		}
	}
	function get_gender($id_number = '0000000000000') {
		$splitted_id = str_split($id_number);
		$DoB         = (int) $splitted_id[ 4 ] . $splitted_id[ 5 ];
		$MoB         = (int) $splitted_id[ 2 ] . $splitted_id[ 3 ];
		$YoB         = (int) $splitted_id[ 0 ] . $splitted_id[ 1 ];
		if ($MoB <= 12 && $DoB <= 31) {
			if ($splitted_id[ 6 ] < 5) {
				$sex = 2;
			} else {
				$sex = 1;
			}
			return $sex;
		} else {
			return '';
		}
	}
	function get_password($id_number = '0000000000000') {
		return password_hash(substr($id_number, -6), PASSWORD_DEFAULT);
	}
	function decode_id_number($id_number = '0000000000000') {
		$splitted_id = str_split($id_number);
		$DoB         = (int) $splitted_id[ 4 ] . $splitted_id[ 5 ];
		$MoB         = (int) $splitted_id[ 2 ] . $splitted_id[ 3 ];
		$YoB         = (int) $splitted_id[ 0 ] . $splitted_id[ 1 ];
		if ($MoB <= 12 && $DoB <= 31) {
			if ($splitted_id[ 0 ] <= 6) {
				$YoB = 2000 + $YoB;
			} elseif ($splitted_id[ 0 ] >= 7) {
				$YoB = 1900 + $YoB;
			}
			$data['birthday'] = strtotime($DoB . '-' . $MoB . '-' . $YoB);
			if ($splitted_id[ 6 ] > 4) {
				$data['sex'] = '1';
			}
			if ($splitted_id[ 6 ] < 5) {
				$data['sex'] = '2';
			}
			if ($splitted_id[ 10 ] < 1) {
				$data['address'] = 'South Africa';
			} else {
				$data['address'] = 'International';
			}
			return $data;
		}
	}
	function decode_phone($phone = '0000000000') {
		$phone = str_replace(array(
			'(',
			')',
			'-',
			' '
		), '', $phone);
		if (substr($phone, 0, 3) == '+27') {
			$phone = $phone;
		} else if (substr($phone, 0, 2) == '27') {
			$phone = '+' . $phone;
		} else if ($phone[0] == '0') {
			$phone = '+27' . substr($phone, -9);
		} else {
			$phone = $phone;
		}
		return $phone;
	}
	function get_image_url($type = '', $id = '') {
		if (file_exists('uploads/' . $type . '_image/' . $this->upload_path . '/' . $id . '.jpg'))
			$image_url = base_url() . 'uploads/' . $type . '_image/' . $this->upload_path . '/' . $id . '.jpg';
		else
			$image_url = base_url() . 'uploads/user.jpg';
		return $image_url;
	}
	function get_grade($mark_obtained) {
		$query  = $this->db->get('grade');
		$grades = $query->result();
		foreach ($grades as $row) {
			if ($mark_obtained >= $row->mark_from && $mark_obtained <= $row->mark_upto)
				return $row->grade_point;
		}
	}
	function reporting_schedule_mark($account_id, $class_id, $section_id, $subject_id, $term, $year) {
		$this->db->where('account_id', $account_id);
		$this->db->where('class_id', $class_id);
		$this->db->where('section_id', $section_id);
		$this->db->where('subject_id', $subject_id);
		$this->db->where('term', $term);
		$this->db->where('year', $year);
		$query         = $this->db->get('mark')->result();
		$mark          = 0;
		$total         = 0;
		$count_tasks   = 0;
		$obtained      = 0;
		$mark_obtained = 0;
		foreach ($query as $marks) {
			$mark_obtained = $mark_obtained + $marks->mark_obtained;
			$total         = $total + $this->exam_info($marks->exam_id)->total;
			$count_tasks++;
		}
		$count_tasks = $count_tasks == 0 ? 1 : $count_tasks;
		$total       = $total == 0 ? 1 : $total;
		$obtained    = $mark_obtained / $total * 100;
		return round($obtained);
	}
	function get_guardian_students($account_id) {
		$this->db->where('guardian_id', $account_id);
		$students = $this->db->get('account');
		if ($students->num_rows() > 0) {
			return $students->result();
		} else {
			return $this->no_data;
		}
	}
	function get_teacher_classes($account_id) {
		$this->db->select('class_id', 'account_id');
		$subjects    = $this->db->get('subject')->result_array();
		$empty_array = [];
		foreach ($subjects as $subject) {
			$decoded_subjects = json_decode($subject, true);
			foreach ($decoded_subjects as $ds) {
				if ($ds == $account_id) {
				}
			}
		}
	}
	function send_marks($class_id, $receiver = array('guardian', 'student'), $term = '', $year = '') {
		$students = $this->db->get_where('enrol', array(
			'class_id' => $class_id
		))->result();
		if (is_array($receiver)) {
			$subjects = $this->db->get_where('subject', array(
				'class_id' => $class_id
			))->result();
			foreach ($students as $student_info) {
				$message        	= '';
				$message2 			= '';
				$account_id    = $this->db->get_where('account', array(
					'account_id' => $student_info->account_id
				))->row()->guardian_id;
				$guardian_phone = $this->guardian_info($account_id)->phone;
				$subject        = array();
				foreach ($subjects as $subject_info) {
					$exams          = $this->db->get_where('exam', array(
						'class_id' => $class_id,
						'subject_id' => $subject_info->subject_id
					));
					$mark_obtained  = 0;
					$total          = 0;
					$number_of_task = $exams->num_rows() > 0 ? 0 : 1;
					foreach ($exams->result() as $exam) {
						$out_of = $this->exam_info($exam->exam_id)->total;
						$marks  = $this->db->get_where('marks', array(
							'exam_id' => $exam->exam_id,
							'account_id' => $student_info->account_id,
							'term' => $term
						))->row()->mark_obtained;
						$m      = $marks / $out_of * 100;
						$mark_obtained += $marks;
						$number_of_task++;
					}
					$mark_obtained                        = $mark_obtained / $number_of_task;
					$subject[ $subject_info->subject_id ] = $mark_obtained;
				}
				foreach ($subject as $msg => $mrks) {
					$message2 .= $this->subject_info($msg)->name . ' = ' . $mrks . '\n';
				}
				
				if (in_array('guardian', $receiver)) {
					$message        .= 'Dear Parent, marks for ' . $this->student_info($student_info->account_id)->name . ' ' . $this->student_info($student_info->account_id)->surname . '\n';
					$message		.= $message2;
					$this->sms->send_sms($message, $guardian_phone);
				}
				if (in_array('student', $receiver)) {
					$message        .= 'Dear ' . $this->student_info($student_info->account_id)->name . ' ' . $this->student_info($student_info->account_id)->surname . ', \n';
					$message		.= $message2;
					$this->sms->send_sms($message, $student_info->phone);
				}
				
			}
		}
		return true;
	}
	function archive($do = '', $what = 'account', $where = '', $id = '') {
		$query = $this->db->get_where($where, array(
			'account_id' => $id
		));
		if ($query->num_rows() > 0) {
			$this->db->where('account_id', $id);
			$this->db->update($where, array(
				'status' => $do
			));
			return 'archived';
		} else {
			return 'failed';
		}
	}
	function send_message() {
		$message              = $this->input->post('message');
		$timestamp            = now();
		$reciever             = $this->input->post('reciever');
		$sender               = $this->session->userdata('account_id');
		$_security_code[]	  = $sender;
		$_security_code[]	  = $reciever ;
		sort($_security_code);
		$_security_code		  = implode('_', $_security_code);
		$security_code        = sha1($_security_code);
		$sender_info          = $this->ilss->account_info($this->account_id);
		$sender_extract       = $message;
		$message              = $sender_extract;
		$receiver_account_id  = $reciever; //note this
		$receveraccount_type = $this->account_info($reciever)->account_type;
		//$recever_account_id   = $account_type_array[ 1 ];
		
		$check1               = $this->db->get_where('message_thread', array(
			'sender' => $sender,
			'reciever' => $reciever
		))->num_rows();
		$check2               = $this->db->get_where('message_thread', array(
			'sender' => $reciever,
			'reciever' => $sender
		))->num_rows();
		if ($check1 < 1 && $check2 < 1) {
			$message_thread_code                        = substr(md5(rand(10000000000000, 2000000000000000)), 0, 30);
			$data_message_thread['message_thread_code'] = $message_thread_code;
			$data_message_thread['sender']              = $sender;
			$data_message_thread['reciever']            = $reciever;
			$data_message_thread['security']            = $security_code;
			$this->db->insert('message_thread', $data_message_thread);
			log_message('info', 'New message thread code created ');
			$this->create_log('New message thread code created');
			
		}
		if ($check1 > 0)
			$message_thread_code = $this->db->get_where('message_thread', array(
				'sender' => $sender,
				'reciever' => $reciever
			))->row()->message_thread_code;
		if ($check2 > 0)
			$message_thread_code = $this->db->get_where('message_thread', array(
				'sender' => $reciever,
				'reciever' => $sender
			))->row()->message_thread_code;
		
		$data_message['message_thread_code'] = $message_thread_code;
		$data_message['message']             = $this->encrypt->encode($message, $security_code);
		$data_message['message_for_sender']  = $data_message['message'];
		$data_message['message_for_receiver']= $data_message['message'];
		$data_message['sender']              = $sender;
		$data_message['read_status']         = '0';
		$data_message['timestamp']           = $timestamp;
		$this->db->insert('message', $data_message);
		
		//$this->db->update('message_thread', ['timestamp' => $timestamp], ['message_thread_code' => $message_thread_code]);
		
		log_message('info', 'new chat message saved');
		$this->create_log('new chat message saved');
		
		if ($receveraccount_type == 'student' || 
			$receveraccount_type == 'guardian' && 
			$this->account_type == 'principal' || 
			$this->account_type == 'admin' || 
			$this->account_type == 'teacher' || 
			$this->account_type == 'accountant') {
			$phone = $this->account_info($recever_account_id)->phone;
			$this->sms->send($message, $phone);
			log_message('info', 'send chat message to user by sms');
			$this->create_log('send chat message to user by sms');
		}
		return $message_thread_code;
	}
	function chat_badge(){
		$my_chat_id = $this->session->account_id;
		$receiver_count = 0;
		$this->db->where('sender', $my_chat_id);
		$this->db->or_where('reciever', $my_chat_id);
		$message_theads = $this->db->get('message_thread');
		log_message('debug', 'Count thread '. $message_theads->num_rows());
		if($message_theads->num_rows() > 0){
			foreach($message_theads->result() as $thread_code){
				
				$this->db->where('sender !=', $my_chat_id);
				$this->db->where('read_status !=', '1');
				$this->db->where('message_thread_code', $thread_code->message_thread_code);
				$unread_messages = $this->db->get('message')->num_rows();
				$receiver_count = $receiver_count + $unread_messages;
			}
		}
		log_message('debug', 'found '.$receiver_count);
		return $receiver_count;
		
	}
	
	function listen(){
		$timestamp = now();
		$this->db->where('sender', $this->session->account_id);
		$this->db->or_where('reciever', $this->session->account_id);
		$message_theads = $this->db->get('message_thread');
		log_message('debug', 'Count thread '. $message_theads->num_rows());
		$count = 0;
		if($message_theads->num_rows() > 0){
			
			foreach($message_theads->result() as $thread_code){
				$this->db->where('message_thread_code', $thread_code->message_thread_code);
				//$this->db->where('timestamp <=', $timestamp);
				$new_messages = $this->db->get('message')->num_rows();
				if($new_messages > 0){
					$count += $new_messages;
				}	
			}
		}
		log_message('debug', 'New messages '.$count);
		
		return $count;
	}
	function update_badge(){
		$use_id 	= $this->input->post('account_id');
		$my_chat_id = $this->session->userdata('account_id');
		$check1     = $this->db->get_where('message_thread', array(
			'sender' => $my_chat_id,
			'reciever' => $use_id
		));
		$check2     = $this->db->get_where('message_thread', array(
			'sender' => $use_id,
			'reciever' => $my_chat_id
		));
		
		if ($check1->num_rows() > 0 || $check2->num_rows() > 0) {
			if($check1->num_rows() > 0){
				$thread_code = $check1->row()->message_thread_code;
			}else{
				$thread_code = $check2->row()->message_thread_code;
			}
			$this->db->where('message_thread_code', $thread_code);
			$this->db->where('sender !=', $use_id);
			$this->db->where('read_status !=', '1');
			$this->db->update('message', array('read_status' => '1'));
		}
		
		
		return true;
		
	}
	function conversation_history($use_id) {
		$my_chat_id = $this->session->userdata('account_id');
		$check1     = $this->db->get_where('message_thread', array(
			'sender' => $my_chat_id,
			'reciever' => $use_id
		))->num_rows();
		$check2     = $this->db->get_where('message_thread', array(
			'sender' => $use_id,
			'reciever' => $my_chat_id
		))->num_rows();
		if ($check1 > 0) {
			$message_thread_code = $this->db->get_where('message_thread', array(
				'sender' => $my_chat_id,
				'reciever' => $use_id
			))->row()->message_thread_code;
			return $message_thread_code;
		}
		if ($check2 > 0) {
			$message_thread_code = $this->db->get_where('message_thread', array(
				'sender' => $use_id,
				'reciever' => $my_chat_id
			))->row()->message_thread_code;
			return $message_thread_code;
		}
		if ($check2 < 1 && $check1 < 1) {
			return '';
		}
	}
	function encryption_key($use_id) {
		$my_chat_id = $this->session->userdata('account_id');
		$check1     = $this->db->get_where('message_thread', array(
			'sender' => $my_chat_id,
			'reciever' => $use_id
		))->num_rows();
		$check2     = $this->db->get_where('message_thread', array(
			'sender' => $use_id,
			'reciever' => $my_chat_id
		))->num_rows();
		if ($check1 > 0) {
			$security = $this->db->get_where('message_thread', array(
				'sender' => $my_chat_id,
				'reciever' => $use_id
			))->row()->security;
			return $security;
		}
		if ($check2 > 0) {
			$security = $this->db->get_where('message_thread', array(
				'sender' => $use_id,
				'reciever' => $my_chat_id
			))->row()->security;
			return $security;
		}
		if ($check2 < 1 && $check1 < 1) {
			return '';
		}
	}
	
	
	function get_chats($chat_history = '') {
		$chat_history = substr($chat_history, 1);
		$messages = json_decode(json_encode([]));
		if($chat_history != ''){
			$this->db->where('message_thread_code', $chat_history);
			$this->db->order_by('timestamp ASC');
			$messages = $this->db->get('message')->result();
			$security = $this->db->get_where('message_thread', ['message_thread_code' => $chat_history ])->row()->security;
			$data = [];
			foreach($messages as $row){
				
				$string = $this->encrypt->decode($row->message, $security);
				$message = parse_smileys($string, base_url() .'assets/images/smileys/');
				$data['messages'][] = ['time' => date('d M Y', $row->timestamp),
										'message' => $message, 
										'fromOpponent' => $this->account_type != $row->sender ? false : true, 
										'unread' => $this->account_type == $row->sender ? true :false];
			}
		}
		
		return  json_encode($data);
	}
	function get_office_staff($account_type) {
		$this->db->where('account_id  !=', $this->session->userdata('account_id'));
		$this->db->where('account_type', $account_type);
		$data = $this->db->get('account')->result();
		return $data;
	}
	function get_student_chat($class_id = '', $year = '') {
		
		$this->db->where('class_id', $class_id);
		$this->db->where('account_id  !=', $this->session->userdata('account_id'));
		$this->db->where('year', $year);
		
		$students = $this->db->get('enrol')->result();
		return $students;
	}
	function profile_picture() {
		$file = 'uploads/' . $this->upload_path . '/profiles/' . $this->session->userdata('account_id') . '.jpg';
		if (file_exists($file))
			$image_url = base_url() . $file;
		else
			$image_url = base_url() . 'uploads/user.jpg';
		return $image_url;
	}
	function school_logo() {
		$file = 'uploads/' . $this->upload_path . '/logo/logo.png';
		if (file_exists($file))
			$image_url = base_url() . $file;
		else 
			$image_url = base_url() . 'assets/images/ilss-name.png';
		return $image_url;
	}
	function update_profile() {
		$data['phone']       = $this->input->post('phone');
		$data['home']        = $this->input->post('home');
		$data['email']       = $this->input->post('email');
		$data['address']     = $this->input->post('address');
		$data['postal_code'] = $this->input->post('postal_code');
		$this->db->where('account_id', $this->session->userdata('account_id'));
		$this->db->update('account', $data);
		move_uploaded_file($_FILES['userfile']['tmp_name'], 'uploads/admin_image/' . $this->upload_path . '/' . $this->session->userdata('account_id') . '.jpg');
		$this->session->set_flashdata('flash_message', translate($this->account_type . '_account_updated'));
	}
	function update_password() {
		$password = $this->input->post('password');
		if ($password != NULL && $password != '') {
			$data['email']    = $this->input->post('email');
			$data['password'] = password_hash($this->input->post('password'), PASSWORD_DEFAULT);
			$this->db->where('account_id', $this->session->userdata('account_id'));
			$this->db->update('account', $data);
			$this->session->set_flashdata('flash_message', translate($this->account_type . '_account_updated'));
		} else {
			$this->session->set_flashdata('flash_message', translate('error_occured'));
		}
	}
	function get_chat() {
		$users                                                    = $this->db->get($this->config->item('sess_save_path'))->result();
		$data[ $this->session->userdata('account_type') . '_id'] = $this->session->userdata($this->session->userdata('account_type') . '_id');
		foreach ($users as $row) {
			if ($row->data == !'' && $row->timestamp > 0) {
				$serialized_string = $row->data;
				$variables         = array();
				$a                 = preg_split("/(\w+)\|/", $serialized_string, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
				for ($i = 0; $i < count($a); $i = $i + 2) {
					if (isset($a[ $i + 1 ])) {
						$variables[ $a[ $i ] ] = unserialize($a[ $i + 1 ]);
					}
				}
				if (isset($variables['admin_login']) || isset($variables['teacher_login'])) {
					if ($variables['account_type'] == 'admin') {
						$table    = 'admin';
						$user     = 'admin_id';
						$identity = $variables[ $user ];
					}
					if ($variables['account_type'] == 'account') {
						$table    = 'account';
						$user     = 'account_id';
						$identity = $variables[ $user ];
					}
				}
				$userdata                 = $this->db->get_where($table, array(
					$user => $identity
				))->row();
				$data[ $userdata->$user ] = $userdata->$user;
			}
		}
		return $data;
	}
	function get_student_marks_raw($account_id, $year = '') {
		$this->db->where('account_id', $account_id);
		$this->db->where('year', $year);
		$marks = $this->db->get('mark')->result();
		return $marks;
	}
	function get_student_marks_calculated($account_id) {
	}
	function student_attendance_status($account_id, $week, $day, $year) {
		$this->db->where('account_id', $account_id);
		$this->db->where('year', $year);
		$this->db->where('week', $week);
		$status = $this->db->get('attendance');
		if ($status->num_rows() > 0) {
			$sta = $status->row()->$day;
			if ($sta == 1) {
				return '<i class="entypo-record hidden-print" style="color: #00a651;"></i>';
			} else {
				return '<i class="entypo-record" style="color: #ee4749;"></i>';
			}
		} else {
			return '';
		}
	}
	function attendance_week_status($account_id, $week, $day, $year) {
		$this->db->where('account_id', $account_id);
		$this->db->where('year', $year);
		$this->db->where('week', $week);
		$status = $this->db->get('attendance');
		if($status->num_rows() > 0){
			return true;
		}else{
			return false;
		}
	}
	
	public function generate_pdf($content, $name = 'download.pdf', $output_type = '', $footer = '', $margin_bottom = '', $header = '', $margin_top = '', $orientation = 'P')
    {
		
        if (!$output_type) {
            $output_type = 'D';
        }
        if (!$margin_bottom) {
            $margin_bottom = 10;
        }
        if (!$margin_top) {
            $margin_top = 20;
        }
        //$this->load->library('pdf');
		log_message('info', 'PDF LOADED');
		require_once APPPATH . "/third_party/MPDF/mpdf.php";
        $pdf = new mPDFs('utf-8', 'A4' . $orientation, '13', '', 10, 10, $margin_top, $margin_bottom, 9, 9);
		log_message('info', 'CLASS PDF FINE');
        $pdf->debug = false;
        $pdf->autoScriptToLang = true;
        $pdf->autoLangToFont = true;
        $pdf->SetProtection(array('print')); // You pass 2nd arg for user password (open) and 3rd for owner password (edit)
        //$pdf->SetProtection(array('print', 'copy')); // Comment above line and uncomment this to allow copying of content
        $pdf->SetTitle('title');
        $pdf->SetAuthor('site_name');
        $pdf->SetCreator('site nme');
        $pdf->SetDisplayMode('fullpage');
		log_message('info', 'START STYLE GETTING CONTENTS');
		
		//$stylesheet = '.class{page:30px;}';
        //$pdf->WriteHTML($stylesheet, 0);
        // $pdf->SetFooter($this->Settings->site_name.'||{PAGENO}/{nbpg}', '', TRUE); // For simple text footer

        if (is_array($content)) {
            $pdf->SetHeader('site name ||{PAGENO}/{nbpg}', '', TRUE); // For simple text header
            $as = sizeof($content);
            $r = 1;
            foreach ($content as $page) {
                $pdf->WriteHTML($page['content']);
                if (!empty($page['footer'])) {
                    $pdf->SetHTMLFooter('<p class="text-center">' . $page['footer'] . '</p>', '', true);
                }
                if ($as != $r) {
                    $pdf->AddPage();
                }
                $r++;
            }

        } else {

            //$pdf->WriteHTML('hello world');
            if ($header != '') {
                $pdf->SetHTMLHeader('<p class="text-center">' . $header . '</p>', '', true);
            }
            if ($footer != '') {
                $pdf->SetHTMLFooter('<p class="text-center">' . $footer . '</p>', '', true);
            }

        }

        if ($output_type == 'S') {
            $file_content = $pdf->Output('', 'S');
			$this->load->helper('file');
            write_file('uploads/' . $name, $file_content);
            return true;
        } else {
            $pdf->Output($name, $output_type);
        }
    }
	
	function registeraccount_types(){
		$accounts = array();
		if($this->account_type == 'admin'){
			$accounts['admin'] 	= 'admin';
			$accounts['principal'] 	= 'principal';
			$accounts['teacher'] 	= 'teacher';
			$accounts['student'] 	= 'student';
			$accounts['guardian'] 	= 'guardian';
			$accounts['librarian'] 	= 'librarian';
			$accounts['accountant'] = 'accountant';
			$accounts['clerk'] 		= 'clerk';
		}
		else if($this->account_type == 'principal'){
			$accounts['admin'] 	= 'admin';
			$accounts['teacher'] 	= 'teacher';
			$accounts['student'] 	= 'student';
			$accounts['guardian'] 	= 'guardian';
			$accounts['librarian'] 	= 'librarian';
			$accounts['accountant'] = 'accountant';
			$accounts['clerk'] 		= 'clerk';
		}elseif($this->account_type == 'clerk'){
			$accounts['admin'] 		= 'admin';
			$accounts['teacher'] 	= 'teacher';
			$accounts['student'] 	= 'student';
			$accounts['guardian'] 	= 'guardian';
			$accounts['librarian'] 	= 'librarian';
			$accounts['accountant'] = 'accountant';
			$accounts['principal'] 	= 'principal';
			
		}else if($this->account_type == 'teacher'){
			$accounts['student'] 	= 'student';
			$accounts['guardian'] 	= 'guardian';
		}
		return $accounts;
	}
	
	function race(){
		$races['african'] 	= 'african';
		$races['white'] 		= 'white';
		$races['coloured'] 	= 'coloured';
		$races['indian'] 	= 'indian';
		return $races;
	}
	
	function languages(){
		$languages['english'] 	= 'english';
		$languages['xitsonga'] 	= 'xitsonga';
		$languages['afrikaans'] 	= 'afrikaans';
		$languages['sesotho'] 	= 'sesotho';
		$languages['sepedi'] 	= 'sepedi';
		$languages['xhosa'] 		= 'xhosa';
		$languages['isizulu'] 	= 'isizulu';
		$languages['ndebele'] 	= 'ndebele';
		return $languages;
	}
	
	function get_student_complete_results($account_id, $term, $year){
		$student_enrol_info = $this->enrol_info($account_id, $year);
		$class_id			= $student_enrol_info->class_id;
		$section_id			= $student_enrol_info->section_id;
		
		$this->db->where('class_id');
		$student_subjects = [];
		$subjects = $this->db->get('subject')->result();
		foreach($subjects as $subject){
			if(in_array($section_id, json_decode($subject->section_id, true))){
				$student_subjects[] = $subject->subject_id;
			}
		}
		if(count($student_subjects) > 0){
			foreach($student_subjects as $subject_id){
				$subject_info = $this->subject_info($subject_id);
				$description = $subject_info->description;
				$pass_mark = $this->settings($description.'_pass_mark');
				$mark = $this->reporting_schedule_mark($account_id, $class_id, $section_id, $subject_id, $term, $year);
				$count = 0;
				if($mark >= $pass_mark){
					$count++;
				}
				if($count >= $this->settings('subjects_to_pass')){
					return true;
				}
			}
		}
		return false;
	}
	
	function tm_builder(){
		//term mark made of?
		if($action = 'add'){
			$subject_id = $this->input->post('subject_id');
			$term 		= $this->input->post('term');
			$terms 		= $this->input->post('terms');
			$convertion = $this->input->post('convertion');
			$year	    = $this->input->post('year');
			
			$new_terms = [];
			foreach($terms as $t){
				if($t <= $term){
					if($term != $t){
						$new_terms[] = $t;
					}
				}
			}
			$data['subject_id'] = $subject_id;
			$data['term'] 		= $term;
			$data['terms'] 		= json_encode($new_terms);
			$data['convertion'] = $convertion > 100 ? 0 : $convertion;
			$data['remainder']  = 100 - $data['convertion'];
			$data['year']  = 100 - $year;
			
			$this->db->where('subject_id', $subject_id);
			$this->db->where('year', $year);
			$query = $this->db->get('tm_builder')->num_rows();
			if($query > 0){
				$this->db->where('subject_id', $subject_id);
				$this->db->where('year', $year);
				$this->db->update('tm_builder', $data);
			}else{
				$this->db->insert('tm_builder');
			}
		}else if($action = 'update'){
			$subject_id = $this->input->post('subject_id');
			$term 		= $this->input->post('term');
			$terms 		= $this->input->post('terms');
			$convertion = $this->input->post('convertion');
			
			$new_terms = [];
			foreach($terms as $t){
				if($t <= $term){
					if($term != $t){
						$new_terms[] = $t;
					}
				}
			}
			$data['subject_id'] = $subject_id;
			$data['term'] 		= $term;
			$data['terms'] 		= json_encode($new_terms);
			$data['convertion'] = $convertion > 100 ? 0 : $convertion;
			$data['remainder']  = 100 - $data['convertion'];
			$data['year']  = 100 - $year;
			$this->db->where('subject_id', $subject_id);
			$this->db->where('year', $this->year);
			$this->db->update('tm_builder', $data);
			
		}else if($action = 'delete'){
			$this->db->where('subject_id', $this->input->post('delete'));
			$this->db->where('year', $this->year);
			$this->db->delete('tm_builder');
		}
		
		
	}
	
	function teacher_classes($account_id){
		$account_id = ! is_null($account_id) ? $account_id : $this->account_id;
		$classes = []; //sections
		$_class = []; //final return
		$this->db->where('account_id', $account_id);
		$class = $this->db->get('class');
		if($class->num_rows() > 0){
			foreach($class->result() as $cls){
				$classes[] = $cls->class_id;
			}
		}
		$_class['sectional_head'] = array_unique($classes); //sectional head
		$classes = []; //reset array
		$this->db->where('account_id', $account_id);
		$class = $this->db->get('section');
		if($class->num_rows() > 0){
			foreach($class->result() as $cls){
				$classes[] = $cls->class_id;
			}
		}
		$_class['class_teacher'] = array_unique($classes); //class teacher
		
		$classes = []; //reset array
		$subjects = $this->db->get('subject')->result();
		foreach($subjects as $subject){
			$teachers = json_decode($subject->account_id, true);
			
			foreach($teachers as $teacher){
				if($teacher == $account_id){
					$classes[] = $subject->class_id;
				}
			}
		}
		$_class['subject_teacher'] 	= array_unique($classes); //class teacher
		$_class['all_classes']  	= array_unique(array_merge($_class['subject_teacher'], $_class['class_teacher'], $_class['sectional_head']));
		
		return $_class;
		
	}
	function account_info_update(){
		$data[ 'name' ]      = ucwords(strtolower($this->input->post('name')));
		$data[ 'surname' ]   = ucwords(strtolower($this->input->post('surname')));
		$data[ 'id_number' ] = $this->input->post('id_number');
		$data[ 'title' ]     = ucwords(strtolower($this->input->post('title')));
		$data[ 'birthday' ]  = $this->ilss->get_birthday($data[ 'id_number' ]);
		$data[ 'sex' ]       = $this->ilss->get_gender($data[ 'id_number' ]);$data[ 'phone' ]     = $this->ilss->decode_phone($this->input->post('phone'));
		$data[ 'address' ]   = ucwords(strtolower($this->input->post('address')));
		$data[ 'email' ]     = strtolower($this->input->post('email'));
		move_uploaded_file($_FILES['userfile']['tmp_name'], 'uploads/profiles/' . $this->upload_path . '/' . $this->input->post('update') . '.jpg');
		$this->db->where('account_id', $this->input->post('update'));
		$this->db->update('account', $data);
		$this->session->set_flashdata('flash_message', translate('account_information_updated'));
	}
	function account_delete(){
		$this->db->where('account_id', $this->input->post('delete'));
		$this->db->update('account', ['status' => 'deleted']); 
		$this->session->set_flashdata('flash_message', translate('data_deleted'));
	}
	function manage_document($action = ''){
		if ($action == 'create') {
			$data[ 'title' ]         = ucwords($this->input->post('title'));
			$data[ 'name' ]          = ucwords($this->input->post('name'));
			$data[ 'content' ]       = $this->input->post('content');
			$data[ 'account_type' ]  = $this->account_type;
			$data[ 'account_id' ]    = $this->session->userdata('account_id');
			$data[ 'date_created' ]  = now();
			$data[ 'date_modified' ] = now();
			$this->db->insert('document', $data);
			$this->session->set_flashdata('flash_message', translate('document_created'));
		} elseif ($action == 'update') {
			$data[ 'title' ]         = ucwords($this->input->post('title'));
			$data[ 'name' ]          = ucwords($this->input->post('name'));
			$data[ 'content' ]       = $this->input->post('content');
			$data[ 'date_modified' ] = now();
			$this->db->where('document_id', $this->input->post('update'));
			$this->db->update('document', $data);
			$this->session->set_flashdata('flash_message', translate('document_updated'));
			redirect(base_url($this->account_url . '/manage_document'));
		} elseif ($action == 'delete') {
			$this->db->where('document_id', $this->input->post('delete'));
			$this->db->delete('document');
			$this->session->set_flashdata('flash_message', translate('document_deleted'));
			redirect(base_url($this->account_url . '/manage_document'));
		} 
	}
	
	function _template_download($template){
		if($template == 'bulk_enrol_template'){
			force_download(APPPATH . 'storage/template/bulk_enrol_template.xlsx', null);
			//echo 'done';
		}
		
		
	}
	
	function upload_academic_syllabus(){
		$data[ 'academic_syllabus_code' ] = substr(md5(rand(0, 1000000)), 0, 7);
		$data[ 'title' ]                  = $this->input->post('title');
		$data[ 'description' ]            = $this->input->post('description');
		$data[ 'class_id' ]               = $this->input->post('class_id');
		$data[ 'uploader_type' ]          = $this->account_type;
		$data[ 'uploader_id' ]            = $this->account_id;
		$data[ 'year' ]                   = $this->year;
		$data[ 'timestamp' ]              = now();
		$dir  							  = 'uploads/syllabus/'.$this->upload_path;
		move_uploaded_file($_FILES['file_name']['tmp_name'], $dir . '/' .$_FILES[ 'file_name' ][ 'name' ]);
		
		$data[ 'file_name' ] = $_FILES[ 'file_name' ][ 'name' ];
		$this->db->insert('material', $data);
		$this->session->set_flashdata('flash_message', translate('syllabus_uploaded'));
	}
	
	function custom_query($table, $array){
		return $this->db->get_where($table, $array);
	}
	
	function get_videos($class_id = ''){
		
		//$this->db->where('class_id',$class_id);
		$videos = $this->db->get('video')->result();
			
		
		return $videos;
	}
	
	function video_player($video_id =''){
		return $this->db->where('video_id', $video_id)->get('video')->row()->video_url;
	}
	
	function modal_button($text = 'add something', $link = '#'){
		$button = [];
		$_button = '<a herf="#" onClick="showAjaxModal("http://hudson.ilss.com/ILSS/modal/popup/add/account/3");" class="pull-right btn btn-xs btn-default">'.$text.'</a>';
		$button[] = $_button;
		return implode(' ', $button);
	}
	function create_class(){
		$data[ 'name' ]         = $this->input->post('name');
		$data[ 'name_numeric' ] = $this->input->post('name_numeric');
		$data[ 'account_id' ]   = $this->input->post('account_id');
		return $this->db->insert('class', $data);
	}
	
	function update_class(){
		$data[ 'name' ]         = $this->input->post('name');
		$data[ 'name_numeric' ] = $this->input->post('name_numeric');
		$data[ 'account_id' ]   = $this->input->post('account_id');
		$this->db->where('class_id', $this->input->post('update'));
		return $this->db->update('class', $data);
	}
	function delete_class(){
		$tables = array(
			'mark',
			'exam',
			'enrol',
			'library',
			'material',
			'section',
			'subject',
			'timetable',
			'class'
		);
		$this->db->where('class_id', $this->input->post('delete'));
		return $this->db->delete($tables);
	}
	
	function add_video_url(){
		$data['album_id'] = $this->input->post('album_id');
		$data['class_id'] = $this->input->post('class_id');
		$data['name'] = $this->input->post('name');
		$data['description'] = $this->input->post('description');
		$data['video_url'] = $this->input->post('video_url');
		return $this->db->insert('video', $data);
	}
	
	function edit_video_url(){
		$data['album_id'] = $this->input->post('album_id');
		$data['class_id'] = $this->input->post('class_id');
		$data['name'] = $this->input->post('name');
		$data['description'] = $this->input->post('description');
		$data['video_url'] = $this->input->post('video_url');
		$this->db->where('video_id', $this->input->post('update'));
		return $this->db->update('video', $data);
	}
	
	function delete_video_url(){
		$this->db->where('video_id', $this->input->post('delete'));
		return $this->db->delete('video');
	}
	
	function add_video_album(){
		$data['class_id'] = $this->input->post('class_id');
		$data['name'] = $this->input->post('name');
		$data['description'] = $this->input->post('description');
		return $this->db->insert('album', $data);
	}
	
	function edit_video_album(){
		$data['class_id'] = $this->input->post('class_id');
		$data['name'] = $this->input->post('name');
		$data['description'] = $this->input->post('description');
		$this->db->where('video_id', $this->input->post('update'));
		return $this->db->update('album', $data);
	}
	
	function delete_video_album(){
		$this->db->where('album_id', $this->input->post('delete'));
		$table[] = 'videos';
		$table[] = 'album';
		return $this->db->delete($tables);
	}
	
	function create_log( $activity = '' ) {
		$data['timestamp']   = strtotime(date('Y-m-d h:i:s'));
		$data['system_name'] = $this->system_name;
		$data['account_id'] = $this->session->account_id;
		$data['ip']          = $_SERVER[ "REMOTE_ADDR" ];
		if($this->host->internet_connection()){
			$location = new SimpleXMLElement(file_get_contents('http://freegeoip.net/xml/' . $_SERVER["REMOTE_ADDR"]));
			$data['location'] = $location->City . ' , ' . $location->CountryName;
		}
		$data['activity']          = $activity;
		$this->db->insert('log', $data);
	}
	
	function notification($timestamp){
		set_time_limit(0);
		$current_timestamp = isset($timestamp) ? $timestamp : 0;
		
		$max_timestamp_db = $this->db->select_max('timestamp')->get('message')->row()->timestamp;
		while($max_timestamp_db <= $current_timestamp){
			sleep(10);
			clearstatcache();
			$max_timestamp_db = $this->db->select_max('timestamp')->get('message')->row()->timestamp;
		}
		
		return json_encode(
							['message' => /*$this->get_messages($thread_code)*/ '',
								'timestamp' => $max_timestamp_db
							]
						);
	}
	
	function get_messages($chat_history = null) {
		$messages = json_decode(json_encode([]));
		if( ! is_null($chat_history) && $chat_history != ''){
			$this->db->where('message_thread_code', $chat_history);
			$messages = $this->db->get('message')->result();
		}
		
		return  $messages;
	}
	
	/* E- Learning */
	
	function virtual_learning(){
		
	}
	
	function student_class($account_id){
		$this->db->where('year', $this->year);
		$this->db->where('account_id', $account_id);
		return $this->db->get('enrol')->row()->class_id;
	}
	function student_section($account_id){
		$this->db->where('year', $this->year);
		$this->db->where('account_id', $account_id);
		return $this->db->get('enrol')->row()->section_id;
	}
	function load_view($page, $title){
		$this->page_data[ 'page_name' ]  = $page;
		$this->page_data[ 'subdir' ]     = 'online';
		$this->page_data[ 'page_title' ] = $title;
		$this->load->view('body', $this->page_data);
	}
	function word_app(){
		$this->load_view('word_app', 'Word writer');
		$this->page_data[ 'page_name' ]  = 'word_app';
		$this->page_data[ 'subdir' ]     = 'online';
		$this->page_data[ 'page_title' ] = 'WordApp online';
		$this->load->view('body', $this->page_data);
	}
	function study_material($class_id = ''){
		if ($class_id == '')
			$class_id = $this->db->get('class')->first_row()->class_id;
		$this->page_data[ 'page_name' ]  = 'study_materials';
		$this->page_data[ 'subdir' ]     = 'online';
		$this->page_data[ 'page_title' ] = translate('study_material');
		$this->page_data[ 'class_id' ]   = $class_id;
		$this->load->view('body', $this->page_data);;
		
	}
	function get_study_material($class_id){
		return $this->db->get_where('study_material', ['class_id' => $class_id])->result();
	}
	function past_papers($class_id =  ''){
		if ($class_id == '')
			$class_id = $this->db->get('class')->first_row()->class_id;
		$this->page_data[ 'page_name' ]  = 'past_papers';
		$this->page_data[ 'subdir' ]     = 'online';
		$this->page_data[ 'page_title' ] = translate('past_test & exam_papers');
		$this->page_data[ 'class_id' ]   = $class_id;
		$this->load->view('body', $this->page_data);
		
	}
	function cloud(){
		
	}
	function fees(){
		
	}
	function sponserships(){
		
	}
	function timeline(){
		
	}
	function discussion(){
		
	}
	function online_exam(){
		
	}
	function suggestion_box(){
		
	}
	function complaints(){
		
	}
	
}

