<?php
// ============================================================
// Mobitel mSMS Enterprise (ESMS) API Configuration
// ============================================================
define('SMS_WSDL_URL', 'https://msmsenterpriseapi.mobitel.lk/mSMSEnterpriseAPI/mSMSEnterpriseAPI.wsdl');

define('SMS_USERNAME', 'esmsusr_EUMe0xC2');
define('SMS_PASSWORD', 'lsj9StMo');
define('SMS_USER_ID', '');   // Mobitel dunne nathnam empty ('') ekama danna
define('SMS_CUSTOMER', '');  // Mobitel dunne nathnam empty ('') ekama danna

// ★ SENDER ID / MASK — portal eke "Number mask" dropdown eke penuna eka
//   (screenshot eke "SIPWAYCMPUS" widihata thibba)
define('SMS_MASK', 'SIPWAYCMPUS');

define('SMS_ENABLED', true); // testing karaddi false karanna, SMS ekak yanne na