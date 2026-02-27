<?php

/**
 * Created by Illuminate Model.
 * Date: Wed, 24 Jan 2018 12:41:31 +0000.
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model as Eloquent;
//use RTR\Traits\DML;
/**
 * Class TConfiguracionSitio
 * 
 * @property string $idOperador
 * @property string $idConfiguracion_sitios
 * @property string $idPersona
 * @property string $idpais
 * @property string $ididioma
 * @property string $idmoneda
 * @property string $nombreConfiguracionSitio
 * @property string $token
 * @property string $logo
 * @property string $ImagenUsuario_porDefecto
 * @property string $socialLoginDetails
 * @property string $facebookstatus
 * @property string $twitterstatus
 * @property string $googlestatus
 * @property string $instagramstatus
 * @property string $facebookappid
 * @property string $twitterappid
 * @property string $facebooksecret
 * @property string $instagramappid
 * @property string $instagramsecret
 * @property string $twittersecret
 * @property string $googleappid
 * @property string $googlesecret
 * @property string $smtpEnable
 * @property string $smtpSSL
 * @property string $smtpEmail
 * @property string $smtpPassword
 * @property string $smtpPort
 * @property string $smtpHost
 * @property string $paypalType
 * @property string $paypalEmailId
 * @property string $paypalApiUserId
 * @property string $paypalApiPassword
 * @property string $paypalApiSignature
 * @property string $paypalAppId
 * @property string $paypalCcStatus
 * @property string $paypalCcClientId
 * @property string $paypalCcSecret
 * @property string $apiNombrePersona
 * @property string $apiPassword
 * @property string $facebookFooterLink
 * @property string $googleFooterLink
 * @property string $twitterFooterLink
 * @property string $linkedinFooterLink
 * @property string $instagramFooterLink
 * @property string $pinterestFooterLink
 * @property string $androidFooterLink
 * @property string $iosFooterLink
 * @property string $socialloginheading
 * @property string $socialloginheading_es
 * @property string $applinkheading
 * @property string $applinkheading_es
 * @property string $generaltextguest
 * @property string $generaltextguest_es
 * @property string $generaltextuser
 * @property string $searchType
 * @property string $searchList
 * @property string $searchDistance
 * @property string $cancelEnableStatus
 * @property string $tracking_code
 * 
 * @property \App\Models\TPersona $t_persona
 *
 * @package App\modelos
 */
class TConfiguracionSitio extends Eloquent
{
	//use DML;
	protected $primaryKey = 'idConfiguracion_sitios';
	public $incrementing = false;
	public $timestamps = false;

	protected $hidden = [
		'token',
		'facebooksecret',
		'instagramsecret',
		'twittersecret',
		'googlesecret'
	];

	protected $fillable = [
		'idOperador',
		'idPersona',
		'idpais',
		'ididioma',
		'idmoneda',
		'nombreConfiguracionSitio',
		'token',
		'logo',
		'ImagenUsuario_porDefecto',
		'socialLoginDetails',
		'facebookstatus',
		'twitterstatus',
		'googlestatus',
		'instagramstatus',
		'facebookappid',
		'twitterappid',
		'facebooksecret',
		'instagramappid',
		'instagramsecret',
		'twittersecret',
		'googleappid',
		'googlesecret',
		'smtpEnable',
		'smtpSSL',
		'smtpEmail',
		'smtpPassword',
		'smtpPort',
		'smtpHost',
		'paypalType',
		'paypalEmailId',
		'paypalApiUserId',
		'paypalApiPassword',
		'paypalApiSignature',
		'paypalAppId',
		'paypalCcStatus',
		'paypalCcClientId',
		'paypalCcSecret',
		'apiNombrePersona',
		'apiPassword',
		'facebookFooterLink',
		'googleFooterLink',
		'twitterFooterLink',
		'linkedinFooterLink',
		'instagramFooterLink',
		'pinterestFooterLink',
		'androidFooterLink',
		'iosFooterLink',
		'socialloginheading',
		'socialloginheading_es',
		'applinkheading',
		'applinkheading_es',
		'generaltextguest',
		'generaltextguest_es',
		'generaltextuser',
		'searchType',
		'searchList',
		'searchDistance',
		'cancelEnableStatus',
		'tracking_code'
	];

	public function t_persona()
	{
		return $this->belongsTo(\App\Models\TPersona::class, 'idPersona');
	}
}
