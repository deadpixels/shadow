<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
$config = array('account/login' => array(
                    array(
                         'field'   => 'email',
                         'label'   => 'Email',
                         'rules'   => 'trim|required|xss_clean'
                      ),
                    array(
                         'field'   => 'password',
                         'label'   => 'Password',
                         'rules'   => 'trim|required|md5|xss_clean'
                      ),
                ),
                'editemp' => array(
                   array(
                         'field'   => 'idnumber',
                         'label'   => 'Id Numner',
                         'rules'   => 'trim|required|xss_clean'
                      ),
                   array(
                         'field'   => 'firstname',
                         'label'   => 'First Name',
                         'rules'   => 'trim|required|xss_clean'
                      ),
                   array(
                         'field'   => 'lastname',
                         'label'   => 'Last Name',
                         'rules'   => 'trim|required|xss_clean'
                      ),   
                   array(
                         'field'   => 'middlename',
                         'label'   => 'Middle Name',
                         'rules'   => 'trim|xss_clean'
                      ),
                   array(
                         'field'   => 'birthdate',
                         'label'   => 'Birth Date',
                         'rules'   => 'trim|required|xss_clean'
                      ),
                   array(
                         'field'   => 'gender',
                         'label'   => 'Gender',
                         'rules'   => 'trim|required|xss_clean'
                      ),
                   array(
                         'field'   => 'civilstatus',
                         'label'   => 'Civil Status',
                         'rules'   => 'trim|required|xss_clean'
                      ),
                   array(
                         'field'   => 'wtaxcode',
                         'label'   => 'Withholding Tax Code',
                         'rules'   => 'trim|xss_clean'
                      ),
                    array(
                         'field'   => 'email',
                         'label'   => 'Email Address',
                         'rules'   => 'trim|xss_clean|valid_email'
                      ),
                    array(
                         'field'   => 'sssnum',
                         'label'   => 'SSS Number',
                         'rules'   => 'trim|xss_clean'
                      ),
                    array(
                         'field'   => 'phicnum',
                         'label'   => 'PHIC Number',
                         'rules'   => 'trim|xss_clean'
                      )
                ),
                'merit' => array(
                	array(
                		'field'   => 'value',
                        'label'   => 'Value',
                        'rules'   => 'trim|required|xss_clean'
                	),
                	array(
                		'field'   => 'entrydate',
                        'label'   => 'Entry Date',
                        'rules'   => 'trim|required|xss_clean'
                	)
                ),
                'empleave' => array(
                	array(
                		'field'   => 'emergencyleave',
                        'label'   => 'Emergency Leave',
                        'rules'   => 'trim|required|xss_clean'
                	),
                	array(
                		'field'   => 'sickleave',
                        'label'   => 'Sick Leave',
                        'rules'   => 'trim|required|xss_clean'
                	),
                	array(
                		'field'   => 'vacationleave',
                        'label'   => 'Vacation Leave',
                        'rules'   => 'trim|required|xss_clean'
                	)
                ),
                'employee/newemp' => array(
                   array(
                         'field'   => 'idnumber',
                         'label'   => 'Id Numner',
                         'rules'   => 'trim|required|xss_clean'
                      ),
                   array(
                         'field'   => 'firstname',
                         'label'   => 'First Name',
                         'rules'   => 'trim|required|xss_clean'
                      ),
                   array(
                         'field'   => 'lastname',
                         'label'   => 'Last Name',
                         'rules'   => 'trim|required|xss_clean'
                      ),   
                   array(
                         'field'   => 'middlename',
                         'label'   => 'Middle Name',
                         'rules'   => 'trim|required|xss_clean'
                      ),
                   array(
                         'field'   => 'birthdate',
                         'label'   => 'Birth Date',
                         'rules'   => 'trim|required|xss_clean'
                      ),
                   array(
                         'field'   => 'gender',
                         'label'   => 'Gender',
                         'rules'   => 'trim|required|xss_clean'
                      ),
                   array(
                         'field'   => 'civilstatus',
                         'label'   => 'Civil Status',
                         'rules'   => 'trim|required|xss_clean'
                      ),
                   array(
                         'field'   => 'wtaxcode',
                         'label'   => 'Withholding Tax Code',
                         'rules'   => 'trim|xss_clean'
                      ),
                   array(
                         'field'   => 'email',
                         'label'   => 'Email Address',
                         'rules'   => 'trim|xss_clean|valid_email'
                      )
                ),                
                'employee/newgroup' => array(
                	array(
                		'field'   => 'groupname',
                        'label'   => 'Group Name',
                        'rules'   => 'trim|required|xss_clean'
                	),
                	array(
                		'field'   => 'grouphead',
                        'label'   => 'Group Head',
                        'rules'   => 'trim|required|xss_clean'
                	)
                ),
                'groupedit' => array(
                	array(
                		'field'   => 'groupname',
                        'label'   => 'Group Name',
                        'rules'   => 'trim|required|xss_clean'
                	),
                	array(
                		'field'   => 'grouphead',
                        'label'   => 'Group Head',
                        'rules'   => 'trim|required|xss_clean'
                	)
                ),
                'groupmembers' => array(
                	array(
                		'field'   => 'employee',
                        'label'   => 'Employee',
                        'rules'   => 'trim|required|xss_clean'
                	)
                ),
                'payroll/newpayroll' => array(
                	array(
                         'field'   => 'payfrom',
                         'label'   => 'From',
                         'rules'   => 'trim|required|xss_clean'
                      ),
                    array(
                         'field'   => 'payto',
                         'label'   => 'To',
                         'rules'   => 'trim|required|xss_clean'
                      )
                ),
                'newloan' => array(
                	array(
                		'field'   => 'loandate',
                        'label'   => 'Loan Date',
                        'rules'   => 'trim|required|xss_clean'
                	),
                	array(
                		'field'   => 'amount',
                        'label'   => 'Loan Amount',
                        'rules'   => 'trim|required|xss_clean'
                	),
                	array(
                		'field'   => 'intrate',
                        'label'   => 'Interest Rate',
                        'rules'   => 'trim|required|xss_clean'
                	),
                	array(
                		'field'   => 'term',
                        'label'   => 'Term',
                        'rules'   => 'trim|required|xss_clean'
                	)
                ),
                'loanpayment' => array(
                	array(
                		'field'   => 'paydate',
                        'label'   => 'Payment Date',
                        'rules'   => 'trim|required|xss_clean'
                	),
                	array(
                		'field'   => 'amount',
                        'label'   => 'Payment Amount',
                        'rules'   => 'trim|required|xss_clean'
                	)
                ),
                'emp_payroll' => array(
                	array(
                         'field'   => 'employee',
                         'label'   => 'Employee',
                         'rules'   => 'trim|required|xss_clean'
                      ),
                    array(
                    	 'field'   => 'scheduleid',
                         'label'   => 'Schedule',
                         'rules'   => 'trim|xss_clean'
                    )
                ),
                'basic' => array(
		            array(
                         'field'   => 'basic',
                         'label'   => 'Basic',
                         'rules'   => 'trim|required|xss_clean'
                      ),
                    array(
                         'field'   => 'allowance',
                         'label'   => 'Allowance',
                         'rules'   => 'trim|required|xss_clean'
                      ),
                    array(
                         'field'   => 'maxhours',
                         'label'   => 'Max Working Hours',
                         'rules'   => 'trim|required|xss_clean'
                    ),
                    array(
                         'field'   => 'maxbreakhours',
                         'label'   => 'Max Break Hours',
                         'rules'   => 'trim|required|xss_clean'
                    ),
                    array(
                         'field'   => 'maxworkdays',
                         'label'   => 'Max Working Days',
                         'rules'   => 'trim|required|xss_clean'
                    ),
                    array(
                         'field'   => 'nightdiffin',
                         'label'   => 'Night Diff In',
                         'rules'   => 'trim|required|xss_clean'
                    ),
                    array(
                         'field'   => 'nightdiffout',
                         'label'   => 'Night Diff Out',
                         'rules'   => 'trim|required|xss_clean'
                    ),
                    array(
                         'field'   => 'halfday',
                         'label'   => 'Half Day',
                         'rules'   => 'trim|required|xss_clean'
                    )
                ),
                'settings/mailer' => array(
                	array(
                		'field'   => 'email',
                        'label'   => 'Email',
                        'rules'   => 'trim|required|xss_clean|valid_email'
                	),
                	array(
                		'field'   => 'pass',
                        'label'   => 'Password',
                        'rules'   => 'trim|required|xss_clean'
                	)
                ),
                'member/newcompany' => array(
                    array(
                        'field'   => 'name',
                        'label'   => 'Name',
                        'rules'   => 'trim|required|xss_clean'
                    ),
                    array(
                        'field'   => 'shortname',
                        'label'   => 'Short Name',
                        'rules'   => 'trim|xss_clean'
                    ),
                    array(
                        'field'   => 'industryid',
                        'label'   => 'Industry',
                        'rules'   => 'trim|required|xss_clean'
                    ),
                    array(
                        'field'   => 'address',
                        'label'   => 'Address',
                        'rules'   => 'trim|xss_clean'
                    ),
                    array(
                        'field'   => 'phone',
                        'label'   => 'Phone',
                        'rules'   => 'trim|xss_clean'
                    ),
                    array(
                        'field'   => 'email',
                        'label'   => 'Email',
                        'rules'   => 'trim|valid_email|xss_clean'
                    ),
                    array(
                        'field'   => 'company_size',
                        'label'   => 'Company Size',
                        'rules'   => 'trim|required|xss_clean'
                    ),
                ),
                'page/portal' => array(
                    array(
                        'field'   => 'erefcode',
                        'label'   => 'Employee Reference Code',
                        'rules'   => 'trim|required|xss_clean'
                    ),
                    array(
                        'field'   => 'crefcode',
                        'label'   => 'Company Reference Code',
                        'rules'   => 'trim|required|xss_clean'
                    ),
                    array(
                        'field'   => 'username',
                        'label'   => 'Username',
                        'rules'   => 'trim|alpha_dash|required|xss_clean'
                    ),
                    array(
                        'field'   => 'password',
                        'label'   => 'Password',
                        'rules'   => 'trim|required|matches[rpassword]|md5|xss_clean'
                    ),
                    array(
                        'field'   => 'rpassword',
                        'label'   => 'Retype Password',
                        'rules'   => 'trim|required|md5|xss_clean'
                    ),
                ),
                'portal/login' => array(
                    array(
                         'field'   => 'username',
                         'label'   => 'Username',
                         'rules'   => 'trim|required|xss_clean'
                      ),
                    array(
                         'field'   => 'password',
                         'label'   => 'Password',
                         'rules'   => 'trim|required|md5|xss_clean'
                      ),
                ),
				'hrfnc/addevent' => array(
					array(
                         'field'   => 'what',
                         'label'   => 'What',
                         'rules'   => 'trim|required|xss_clean'
                      ),
					  array(
                         'field'   => 'location',
                         'label'   => 'Location',
                         'rules'   => 'trim|required|xss_clean'
                      ),
					  array(
                         'field'   => 'when1',
                         'label'   => 'When1',
                         'rules'   => 'trim|required|xss_clean'
                      ),
					  array(
                         'field'   => 'when12',
                         'label'   => 'When1-time',
                         'rules'   => 'trim|required|xss_clean'
                      ),
					  array(
                         'field'   => 'when2',
                         'label'   => 'When2',
                         'rules'   => 'trim|required|xss_clean'
                      ),
					  array(
                         'field'   => 'when22',
                         'label'   => 'When2-time',
                         'rules'   => 'trim|required|xss_clean'
                      ),
					  array(
                         'field'   => 'description',
                         'label'   => 'Description',
                         'rules'   => 'trim|xss_clean'
                      ),
					  
				),
				'hrfnc/addmemo' => array(
					array(
                         'field'   => 'empid',
                         'label'   => 'Employee Name',
                         'rules'   => 'trim|required|xss_clean'
                      ),
					  array(
                         'field'   => 'memo_type',
                         'label'   => 'Memo Type',
                         'rules'   => 'trim|required|xss_clean'
                      ),
					  array(
                         'field'   => 'memo_date',
                         'label'   => 'Memo Date',
                         'rules'   => 'trim|required|xss_clean'
                      ),
					  array(
                         'field'   => 'memo_content',
                         'label'   => 'Content',
                         'rules'   => 'trim|required|xss_clean'
                      ),
				),
				'temp/schedplotter' => array(
					array(
						'field'   => 'label',
                        'label'   => 'Label',
                        'rules'   => 'trim|required|xss_clean'
					),
				),
				'temp/plotsched' => array(
					array(
						'field'   => 'date_in',
                        'label'   => 'Date In',
                        'rules'   => 'trim|required|xss_clean'
					),
					array(
						'field'   => 'time_in',
                        'label'   => 'Time In',
                        'rules'   => 'trim|required|xss_clean'
					),
					array(
						'field'   => 'date_out',
                        'label'   => 'Date Out',
                        'rules'   => 'trim|required|xss_clean'
					),
					array(
						'field'   => 'time_out',
                        'label'   => 'Time Out',
                        'rules'   => 'trim|required|xss_clean'
					),
				),
            );
