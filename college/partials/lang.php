<?php
/**
 * Endrias Film College — public site i18n helper.
 *
 * Port of src/context/LanguageContext.tsx. Language is 'en' | 'am', chosen via
 * the ?lang= query param (which also sets a cookie so the choice persists,
 * mirroring the React site's localStorage). Default is 'en'.
 *
 * t('English', 'አማርኛ') returns the string for the active language.
 */

if (!function_exists('efc_base_url')) {
    /** Public entry (root index.php front controller). */
    function efc_base_url(): string {
        return 'index.php';
    }
}

if (!function_exists('efc_lang')) {
    function efc_lang(): string
    {
        $lang = $_GET['lang'] ?? $_COOKIE['efc_lang'] ?? 'en';
        return ($lang === 'am') ? 'am' : 'en';
    }
}

if (!function_exists('efc_set_lang_cookie')) {
    /** Persist the ?lang= choice for the visitor. */
    function efc_set_lang_cookie(): void
    {
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'am'], true)) {
            setcookie('efc_lang', $_GET['lang'], [
                'expires'  => time() + 60 * 60 * 24 * 365,
                'path'     => '/',
                'samesite' => 'Lax',
            ]);
        }
    }
}

if (!function_exists('t')) {
    /** Bilingual string: returns Amharic when lang is 'am', else English. */
    function t(string $en, string $am): string
    {
        return efc_lang() === 'am' ? $am : $en;
    }
}

if (!function_exists('bp')) {
    /** Pick the active-language column of a ['en'=>..,'am'=>..] data pair. */
    function bp(array $pair): string
    {
        global $EFC_LANG;
        return $pair[$EFC_LANG] ?? ($pair['en'] ?? '');
    }
}

if (!function_exists('efc_url')) {
    /** Build a public-site URL for a page, preserving the current language. */
    function efc_url(string $page = '', string $query = ''): string
    {
        $params = [];
        if ($page !== '') {
            $params['p'] = $page;
        }
        $lang = efc_lang();
        if ($lang !== 'en') {
            $params['lang'] = $lang;
        }
        if ($query !== '') {
            $params['q'] = $query;
        }
        $qs = $params ? ('?' . http_build_query($params)) : '';
        return efc_base_url() . $qs;
    }
}

// Apply the language cookie for the current request if ?lang= was supplied.
efc_set_lang_cookie();
$EFC_LANG = efc_lang();
$EFC_HTML_LANG = $EFC_LANG === 'am' ? 'am' : 'en';
