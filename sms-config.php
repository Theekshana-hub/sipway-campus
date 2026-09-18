<?php

define('SMS_WSDL_URL', 'http://smeapps.mobitel.lk:8585/EnterpriseSMSV3/EnterpriseSMSWS?wsdl');

define('SMS_USERNAME', 'esmsusr_EUMe0xC2');
define('SMS_PASSWORD', 'lsj9StMo');
define('SMS_USER_ID', '');   // Mobitel dunne nathnam empty ('') ekama danna
define('SMS_CUSTOMER', '');  

// ★ SENDER ID / MASK — Mobitel eken approve karapu Sender ID eka methanata
//   danna (e.g. "SIPWAY"). Nathnam blank karala default number eka use wenawa.
define('SMS_MASK', 'SipwayCampus');

define('SMS_ENABLED', true); // testing karaddi false karanna, SMS ekak yanne na