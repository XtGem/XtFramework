<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *   The MIT License                                                                 *
 *                                                                                   *
 *   Copyright (c) 2011 Povilas Musteikis, UAB XtGem                                 *
 *                                                                                   *
 *   Permission is hereby granted, free of charge, to any person obtaining a copy    *
 *   of this software and associated documentation files (the "Software"), to deal   *
 *   in the Software without restriction, including without limitation the rights    *
 *   to use, copy, modify, merge, publish, distribute, sublicense, and/or sell       *
 *   copies of the Software, and to permit persons to whom the Software is           *
 *   furnished to do so, subject to the following conditions:                        *
 *                                                                                   *
 *    The above copyright notice and this permission notice shall be included in     *
 *   all copies or substantial portions of the Software.                             *
 *                                                                                   *
 *   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR      *
 *   IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,        *
 *   FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE     *
 *   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER          *
 *   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,   *
 *   OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN       *
 *   THE SOFTWARE.                                                                   *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class X_locale
{

    // Following array stolen from Zend Framework
    public static $country_to_locale = array (
        'AD' => 'ca_AD', 'AE' => 'ar_AE', 'AF' => 'fa_AF', 'AG' => 'en_AG', 'AI' => 'en_AI',
        'AL' => 'sq_AL', 'AM' => 'hy_AM', 'AN' => 'pap_AN', 'AO' => 'pt_AO', 'AQ' => 'und_AQ',
        'AR' => 'es_AR', 'AS' => 'sm_AS', 'AT' => 'de_AT', 'AU' => 'en_AU', 'AW' => 'nl_AW',
        'AX' => 'sv_AX', 'AZ' => 'az_Latn_AZ', 'BA' => 'bs_BA', 'BB' => 'en_BB', 'BD' => 'bn_BD',
        'BE' => 'nl_BE', 'BF' => 'mos_BF', 'BG' => 'bg_BG', 'BH' => 'ar_BH', 'BI' => 'rn_BI',
        'BJ' => 'fr_BJ', 'BL' => 'fr_BL', 'BM' => 'en_BM', 'BN' => 'ms_BN', 'BO' => 'es_BO',
        'BR' => 'pt_BR', 'BS' => 'en_BS', 'BT' => 'dz_BT', 'BV' => 'und_BV', 'BW' => 'en_BW',
        'BY' => 'be_BY', 'BZ' => 'en_BZ', 'CA' => 'en_CA', 'CC' => 'ms_CC', 'CD' => 'sw_CD',
        'CF' => 'fr_CF', 'CG' => 'fr_CG', 'CH' => 'de_CH', 'CI' => 'fr_CI', 'CK' => 'en_CK',
        'CL' => 'es_CL', 'CM' => 'fr_CM', 'CN' => 'zh_Hans_CN', 'CO' => 'es_CO', 'CR' => 'es_CR',
        'CU' => 'es_CU', 'CV' => 'kea_CV', 'CX' => 'en_CX', 'CY' => 'el_CY', 'CZ' => 'cs_CZ',
        'DE' => 'de_DE', 'DJ' => 'aa_DJ', 'DK' => 'da_DK', 'DM' => 'en_DM', 'DO' => 'es_DO',
        'DZ' => 'ar_DZ', 'EC' => 'es_EC', 'EE' => 'et_EE', 'EG' => 'ar_EG', 'EH' => 'ar_EH',
        'ER' => 'ti_ER', 'ES' => 'es_ES', 'ET' => 'en_ET', 'FI' => 'fi_FI', 'FJ' => 'hi_FJ',
        'FK' => 'en_FK', 'FM' => 'chk_FM', 'FO' => 'fo_FO', 'FR' => 'fr_FR', 'GA' => 'fr_GA',
        'GB' => 'en_GB', 'GD' => 'en_GD', 'GE' => 'ka_GE', 'GF' => 'fr_GF', 'GG' => 'en_GG',
        'GH' => 'ak_GH', 'GI' => 'en_GI', 'GL' => 'iu_GL', 'GM' => 'en_GM', 'GN' => 'fr_GN',
        'GP' => 'fr_GP', 'GQ' => 'fan_GQ', 'GR' => 'el_GR', 'GS' => 'und_GS', 'GT' => 'es_GT',
        'GU' => 'en_GU', 'GW' => 'pt_GW', 'GY' => 'en_GY', 'HK' => 'zh_Hant_HK', 'HM' => 'und_HM',
        'HN' => 'es_HN', 'HR' => 'hr_HR', 'HT' => 'ht_HT', 'HU' => 'hu_HU', 'ID' => 'id_ID',
        'IE' => 'en_IE', 'IL' => 'he_IL', 'IM' => 'en_IM', 'IN' => 'hi_IN', 'IO' => 'und_IO',
        'IQ' => 'ar_IQ', 'IR' => 'fa_IR', 'IS' => 'is_IS', 'IT' => 'it_IT', 'JE' => 'en_JE',
        'JM' => 'en_JM', 'JO' => 'ar_JO', 'JP' => 'ja_JP', 'KE' => 'en_KE', 'KG' => 'ky_Cyrl_KG',
        'KH' => 'km_KH', 'KI' => 'en_KI', 'KM' => 'ar_KM', 'KN' => 'en_KN', 'KP' => 'ko_KP',
        'KR' => 'ko_KR', 'KW' => 'ar_KW', 'KY' => 'en_KY', 'KZ' => 'ru_KZ', 'LA' => 'lo_LA',
        'LB' => 'ar_LB', 'LC' => 'en_LC', 'LI' => 'de_LI', 'LK' => 'si_LK', 'LR' => 'en_LR',
        'LS' => 'st_LS', 'LT' => 'lt_LT', 'LU' => 'fr_LU', 'LV' => 'lv_LV', 'LY' => 'ar_LY',
        'MA' => 'ar_MA', 'MC' => 'fr_MC', 'MD' => 'ro_MD', 'ME' => 'sr_Latn_ME', 'MF' => 'fr_MF',
        'MG' => 'mg_MG', 'MH' => 'mh_MH', 'MK' => 'mk_MK', 'ML' => 'bm_ML', 'MM' => 'my_MM',
        'MN' => 'mn_Cyrl_MN', 'MO' => 'zh_Hant_MO', 'MP' => 'en_MP', 'MQ' => 'fr_MQ', 'MR' => 'ar_MR',
        'MS' => 'en_MS', 'MT' => 'mt_MT', 'MU' => 'mfe_MU', 'MV' => 'dv_MV', 'MW' => 'ny_MW',
        'MX' => 'es_MX', 'MY' => 'ms_MY', 'MZ' => 'pt_MZ', 'NA' => 'kj_NA', 'NC' => 'fr_NC',
        'NE' => 'ha_Latn_NE', 'NF' => 'en_NF', 'NG' => 'en_NG', 'NI' => 'es_NI', 'NL' => 'nl_NL',
        'NO' => 'nb_NO', 'NP' => 'ne_NP', 'NR' => 'en_NR', 'NU' => 'niu_NU', 'NZ' => 'en_NZ',
        'OM' => 'ar_OM', 'PA' => 'es_PA', 'PE' => 'es_PE', 'PF' => 'fr_PF', 'PG' => 'tpi_PG',
        'PH' => 'fil_PH', 'PK' => 'ur_PK', 'PL' => 'pl_PL', 'PM' => 'fr_PM', 'PN' => 'en_PN',
        'PR' => 'es_PR', 'PS' => 'ar_PS', 'PT' => 'pt_PT', 'PW' => 'pau_PW', 'PY' => 'gn_PY',
        'QA' => 'ar_QA', 'RE' => 'fr_RE', 'RO' => 'ro_RO', 'RS' => 'sr_Cyrl_RS', 'RU' => 'ru_RU',
        'RW' => 'rw_RW', 'SA' => 'ar_SA', 'SB' => 'en_SB', 'SC' => 'crs_SC', 'SD' => 'ar_SD',
        'SE' => 'sv_SE', 'SG' => 'en_SG', 'SH' => 'en_SH', 'SI' => 'sl_SI', 'SJ' => 'nb_SJ',
        'SK' => 'sk_SK', 'SL' => 'kri_SL', 'SM' => 'it_SM', 'SN' => 'fr_SN', 'SO' => 'sw_SO',
        'SR' => 'srn_SR', 'ST' => 'pt_ST', 'SV' => 'es_SV', 'SY' => 'ar_SY', 'SZ' => 'en_SZ',
        'TC' => 'en_TC', 'TD' => 'fr_TD', 'TF' => 'und_TF', 'TG' => 'fr_TG', 'TH' => 'th_TH',
        'TJ' => 'tg_Cyrl_TJ', 'TK' => 'tkl_TK', 'TL' => 'pt_TL', 'TM' => 'tk_TM', 'TN' => 'ar_TN',
        'TO' => 'to_TO', 'TR' => 'tr_TR', 'TT' => 'en_TT', 'TV' => 'tvl_TV', 'TW' => 'zh_Hant_TW',
        'TZ' => 'sw_TZ', 'UA' => 'uk_UA', 'UG' => 'sw_UG', 'UM' => 'en_UM', 'US' => 'en_US',
        'UY' => 'es_UY', 'UZ' => 'uz_Cyrl_UZ', 'VA' => 'it_VA', 'VC' => 'en_VC', 'VE' => 'es_VE',
        'VG' => 'en_VG', 'VI' => 'en_VI', 'VN' => 'vn_VN', 'VU' => 'bi_VU', 'WF' => 'wls_WF', 'WS' => 'sm_WS',
        'YE' => 'ar_YE', 'YT' => 'swb_YT', 'ZA' => 'en_ZA', 'ZM' => 'en_ZM', 'ZW' => 'sn_ZW'
    );


    private static $dictionary = array ();

    private static $locale = null,
                   $default_locale = null;
    

    /**
     * Initialize locale
     * @param string $locale
     */
    public static function init ( $locale = null )
    {
        if ( self::$locale != null ) return;

        self::$default_locale = X::get ( 'config', 'locale', 'default' );
        
        if ( $locale == null ||
                ( !preg_match ( '#^[a-z]+_[a-zA-Z_]+$#', $locale ) &&
                  !preg_match ( '#^[a-z]+_[a-zA-Z]+_[a-zA-Z_]+$#', $locale ) )
           )
        {
            self::autodetect ();
        }
        else
        {
            self::$locale = $locale;
        }

        self::init_locale ();
    }


    /**
     * Attempt to autodetect locale
     */
    private static function autodetect ()
    {
        $locale = false;

        // GeoIP
        if ( function_exists ( 'geoip_country_code_by_name' )
             && isset ( $_SERVER [ 'REMOTE_ADDR' ] ) )
        {
            error::silent ( true );
            $country = geoip_country_code_by_name ( $_SERVER [ 'REMOTE_ADDR' ] );
            error::silent ( false );
            if ( $country )
            {
                $locale = isset ( self::$country_to_locale [ $country ] ) ?
                            self::$country_to_locale [ $country ] : false;
            }
        }

        // Try detecting locale from browser headers
        if ( !$locale )
        {
            if ( isset ( $_SERVER [ 'HTTP_ACCEPT_LANGUAGE' ] ) )
            {
                $languages = explode ( ',', $_SERVER [ 'HTTP_ACCEPT_LANGUAGE' ] );
                foreach ( $languages as $lang )
                {
                    $lang = str_replace ( '-', '_', trim ( $lang ) );
                    if ( strpos ( $lang, '_' ) === false )
                    {
                        if ( isset ( self::$country_to_locale [ strtoupper ( $lang ) ] ) )
                        {
                            $locale = self::$country_to_locale [ strtoupper ( $lang ) ];
                        }
                    }
                    else
                    {
                        $lang = explode ( '_', $lang );
                        if ( count ( $lang ) == 3 )
                        {
                            // language_Encoding_COUNTRY
                            self::$locale = strtolower ( $lang [ 0 ] ) .
                                               ucfirst ( $lang [ 1 ] ) .
                                            strtoupper ( $lang [ 2 ] );
                        }
                        else
                        {
                            // language_COUNTRY
                            self::$locale = strtolower ( $lang [ 0 ] ) .
                                            strtoupper ( $lang [ 1 ] );
                        }
                        return;
                    }
                }
            }
        }

        // Resort to default locale specified in config file
        if ( !$locale )
        {
            self::$locale = self::$default_locale;
        }
    }


    /**
     * Check if config for selected locale exists
     */
    private static function init_locale ()
    {
        if ( !file_exists ( XT_PROJECT_DIR .'/locales/'. self::$locale .'.php' ) )
        {
            self::$locale = self::$default_locale;
        }
    }


    /**
     * Return current locale
     * @return string
     */
    public static function get_current ()
    {
        return self::$locale;
    }


    /**
     * Load a dictionary into a variable
     */
    private static function load_dictionary ( $locale = null, $force = false )
    {
        if ( $locale == null ) $locale = self::$locale;
        if ( !isset ( self::$dictionary [ $locale ] ) || $force )
        {
            self::$dictionary [ $locale ] = include ( XT_PROJECT_DIR .'/locales/'. $locale .'.php' );
        }
    }
    

    /**
     * Translate a key using dictionary
     * @param string Key to be translated
     * @param string optional arguments
     * @return string
     */
    public static function translate ( $key )
    {
        self::init ();
        self::load_dictionary ();

        // Try default dictionary if string not found
        if ( !isset ( self::$dictionary [ self::$locale ] [ $key ] ) )
        {
            if ( self::$locale != self::$default_locale )
            {
                self::load_dictionary ( self::$default_locale );
                if ( isset ( self::$dictionary [ self::$default_locale ] [ $key ] ) )
                {
                    $translation = self::$dictionary [ self::$default_locale ] [ $key ];
                }
                else return $key;
            }
            else return $key;
        }
        else $translation = self::$dictionary [ self::$locale ] [ $key ];

        // Replace arguments
        $args = func_get_args ();
        for ( $i = 1, $max = count ( $args ); $i < $max; $i++ )
        {
            $translation = str_replace ( '{a:'. $i .'}', $args [ $i ], $translation );
        }

        return $translation;
    }

}