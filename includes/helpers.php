<?php
function moota_get_field_setting() {}
function moota_update_field_setting() {}

function bank_listing($bank_id= null) {
	$banks = [
		'bca'                  => 'Bank Central Asia',
		'bcaGiro'              => 'BCA Giro',
		'bcaSyariah'           => 'Bank Central Asia Syariah',
		'bniBisnisSyariah'     => 'Bank Negara Indonesia Bisnis Syariah',
		'bniBisnis'            => 'Bank Negara Indonesia Bisnis',
		'bniSyariah'           => 'Bank Negara Indonesia Syariah',
		'bni'                  => 'Bank Negara Indonesia',
		'bri'                  => 'Bank Rakyat Indonesia',
		'briCms'               => 'BRI CMS',
		'briGiro'              => 'Bank Rakyat Indonesia Giro',
		'briSyariah'           => 'Bank Rakyat Indonesia Syariah',
		'briSyariahCms'        => 'BRI Syariah CMS',
		'bsi'                  => 'Bank Syariah Indonesia',
		'bsiGiro'              => 'Bank Syariah Indonesia Giro',
		'gojek'                => 'GoPay',
		'mandiriBisnis'        => 'Mandiri Bisnis',
		'mandiriMcm2'          => 'Mandiri MCM 2',
		'mandiriMcm'           => 'Mandiri MCM',
		'mandiriOnline'        => 'Mandiri',
		'mandiriSyariah'       => 'Mandiri Syariah',
		'mandiriSyariahBisnis' => 'Mandiri Syariah Bisnis',
		'mandiriSyariahMcm'    => 'Mandiri Syariah MCM',
		'mayBank'              => 'MayBank',
		'megaSyariahCms'       => 'Mega Syariah CMS',
		'muamalat'             => 'Muamalat',
		'ovo'                  => 'OVO'
	];

	if ( $bank_id && ! empty($banks[$bank_id]) ) {
		return $banks[$bank_id];
	}

	return $bank_id;
}
