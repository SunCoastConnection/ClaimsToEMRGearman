<?php

use \SunCoastConnection\ClaimsToEMRGearman\Worker\Claims;
use \SunCoastConnection\ClaimsToEMRGearman\Worker\Credentials;

return [
	'servers' => [
		'127.0.0.1:4730',
	],

	'workers' => [
		'ClaimsToEMR.Credentials.Lookup' => [
			'class' => Credentials\Lookup::class,

			'options' => [
				'Credentials' => [
					'path' => __DIR__.'/../cache/credentials',
				],
			],
		],

		'ClaimsToEMR.Credentials.Register' => [
			'class' => Credentials\Register::class,

			'options' => [
				'Credentials' => [
					'path' => __DIR__.'/../cache/credentials',
				],
			],
		],

		'ClaimsToEMR.Claims.Retrieve' => [
			'class' => Claims\Retrieve::class,

			'options' => [
				'SFTP' => [
					'username' => '',
					'password' => '',
					'privateKey' => [
						'path' => __DIR__.'/../cache/privateKey.key',
						'passphrase' => ''
					],
				],
			]
		],

		'ClaimsToEMR.Claims.Process' => [
			'class' => Claims\Process::class,

			'options' => [
				'Workers' => [
					'Claims.Retrieve' => 'ClaimsToEMR.Claims.Retrieve',
					'Credentials.Lookup' => 'ClaimsToEMR.Credentials.Lookup',
				],

				'Claims' => [
					'Store' => [
						'default' => 'mysql',
						'connections' => [
							'mysql' => [
								'driver'		=> 'mysql',
								'host'			=> 'localhost',
								'port'			=> '3306',
								'database'		=> 'homestead',
								'username'		=> 'homestead',
								'password'		=> 'secret',
								'charset'		=> 'utf8',
								'collation'		=> 'utf8_unicode_ci',
								'prefix'		=> '',
								'strict'		=> false,
								'engine'		=> null,
							],
						],
						'queryLog'				=> __DIR__.'/../cache/database.log',
					],

					'Document' => [
						'delimiters' => [
							'data'				=> '*',
							'repetition'		=> '^',
							'component'			=> ':',
							'segment'			=> '~',
						],
					],

					'Aliases' => [
						// Document Classes
						'Raw'					=> \SunCoastConnection\ClaimsToEMR\Document\Raw::class,
						'Document'				=> \SunCoastConnection\ClaimsToEMR\X12N837\Document::class,
						'Cache'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Cache::class,

						// Envelope Classes
						'InterchangeControl'	=> \SunCoastConnection\ClaimsToEMR\X12N837\Envelope\InterchangeControl::class,
						'FunctionalGroup'		=> \SunCoastConnection\ClaimsToEMR\X12N837\Envelope\FunctionalGroup::class,
						'TransactionSet'		=> \SunCoastConnection\ClaimsToEMR\X12N837\Envelope\TransactionSet::class,

						// Loop Classes
						'Loop1000'				=> \SunCoastConnection\ClaimsToEMR\X12N837\Loop\Loop1000::class,
						'Loop2000'				=> \SunCoastConnection\ClaimsToEMR\X12N837\Loop\Loop2000::class,
						'Loop2010'				=> \SunCoastConnection\ClaimsToEMR\X12N837\Loop\Loop2010::class,
						'Loop2300'				=> \SunCoastConnection\ClaimsToEMR\X12N837\Loop\Loop2300::class,
						'Loop2305'				=> \SunCoastConnection\ClaimsToEMR\X12N837\Loop\Loop2305::class,
						'Loop2310'				=> \SunCoastConnection\ClaimsToEMR\X12N837\Loop\Loop2310::class,
						'Loop2320'				=> \SunCoastConnection\ClaimsToEMR\X12N837\Loop\Loop2320::class,
						'Loop2330'				=> \SunCoastConnection\ClaimsToEMR\X12N837\Loop\Loop2330::class,
						'Loop2400'				=> \SunCoastConnection\ClaimsToEMR\X12N837\Loop\Loop2400::class,
						'Loop2410'				=> \SunCoastConnection\ClaimsToEMR\X12N837\Loop\Loop2410::class,
						'Loop2420'				=> \SunCoastConnection\ClaimsToEMR\X12N837\Loop\Loop2420::class,
						'Loop2430'				=> \SunCoastConnection\ClaimsToEMR\X12N837\Loop\Loop2430::class,
						'Loop2440'				=> \SunCoastConnection\ClaimsToEMR\X12N837\Loop\Loop2440::class,

						// Segment Classes
						'AMT'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\AMT::class,
						'BHT'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\BHT::class,
						'CAS'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\CAS::class,
						'CL1'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\CL1::class,
						'CLM'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\CLM::class,
						'CN1'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\CN1::class,
						'CR1'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\CR1::class,
						'CR2'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\CR2::class,
						'CR3'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\CR3::class,
						'CR4'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\CR4::class,
						'CR5'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\CR5::class,
						'CR6'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\CR6::class,
						'CR7'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\CR7::class,
						'CR8'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\CR8::class,
						'CRC'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\CRC::class,
						'CTP'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\CTP::class,
						'CUR'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\CUR::class,
						'DMG'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\DMG::class,
						'DN1'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\DN1::class,
						'DN2'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\DN2::class,
						'DSB'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\DSB::class,
						'DTP'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\DTP::class,
						'FRM'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\FRM::class,
						'GE'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\GE::class,
						'GS'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\GS::class,
						'HCP'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\HCP::class,
						'HI'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\HI::class,
						'HL'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\HL::class,
						'HSD'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\HSD::class,
						'IEA'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\IEA::class,
						'IMM'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\IMM::class,
						'ISA'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\ISA::class,
						'K3'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\K3::class,
						'LIN'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\LIN::class,
						'LQ'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\LQ::class,
						'LX'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\LX::class,
						'MEA'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\MEA::class,
						'MIA'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\MIA::class,
						'MOA'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\MOA::class,
						'N2'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\N2::class,
						'N3'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\N3::class,
						'N4'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\N4::class,
						'NM1'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\NM1::class,
						'NTE'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\NTE::class,
						'OI'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\OI::class,
						'PAT'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\PAT::class,
						'PER'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\PER::class,
						'PRV'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\PRV::class,
						'PS1'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\PS1::class,
						'PWK'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\PWK::class,
						'QTY'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\QTY::class,
						'REF'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\REF::class,
						'SBR'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\SBR::class,
						'SE'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\SE::class,
						'ST'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\ST::class,
						'SV1'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\SV1::class,
						'SV2'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\SV2::class,
						'SV3'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\SV3::class,
						'SV4'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\SV4::class,
						'SV5'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\SV5::class,
						'SV6'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\SV6::class,
						'SV7'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\SV7::class,
						'SVD'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\SVD::class,
						'TOO'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\TOO::class,
						'UR'					=> \SunCoastConnection\ClaimsToEMR\X12N837\Segment\UR::class,

						// Models Classes
						'address'				=> \SunCoastConnection\ClaimsToEMR\Models\Addresses::class,
						'billing'				=> \SunCoastConnection\ClaimsToEMR\Models\Billing::class,
						'facility'				=> \SunCoastConnection\ClaimsToEMR\Models\Facilities::class,
						'formEncounter'			=> \SunCoastConnection\ClaimsToEMR\Models\FormEncounters::class,
						'form'					=> \SunCoastConnection\ClaimsToEMR\Models\Forms::class,
						'group'					=> \SunCoastConnection\ClaimsToEMR\Models\Groups::class,
						'insuranceCompany'		=> \SunCoastConnection\ClaimsToEMR\Models\InsuranceCompanies::class,
						'insuranceData'			=> \SunCoastConnection\ClaimsToEMR\Models\InsuranceData::class,
						'patientData'			=> \SunCoastConnection\ClaimsToEMR\Models\PatientData::class,
						'phoneNumber'			=> \SunCoastConnection\ClaimsToEMR\Models\PhoneNumbers::class,
						'user'					=> \SunCoastConnection\ClaimsToEMR\Models\Users::class,
						'x12Partners'			=> \SunCoastConnection\ClaimsToEMR\Models\X12Partners::class,
						'pqrsImportFiles'		=> \SunCoastConnection\ClaimsToEMR\Models\PqrsImportFiles::class,
					],
				],
			],
		],
	],
];