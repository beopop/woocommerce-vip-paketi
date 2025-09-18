<?php
/**
 * Direct Serbian translations for immediate use
 * This file provides direct translations without needing .mo file compilation
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get Serbian translation for a text string
 * @param string $text English text to translate
 * @return string Serbian translation
 */
function wvp_translate($text) {
    $translations = array(
        // Main Menu Items
        'WooCommerce VIP Paketi' => 'WooCommerce VIP Пакети',
        'VIP Paketi' => 'VIP Пакети',
        'General Settings' => 'Опште Поставке',
        'Settings' => 'Подешавања',
        'VIP Codes' => 'VIP Кодови',
        'Packages' => 'Пакети',
        'Product Settings' => 'Подешавања Производа',
        'Products' => 'Производи',
        'Reports' => 'Извештаји',
        
        // Common Actions
        'Edit' => 'Измени',
        'Delete' => 'Обриши',
        'Save' => 'Сачувај',
        'Cancel' => 'Откажи',
        'Apply' => 'Примени',
        'Search' => 'Претрага',
        'Filter' => 'Филтер',
        'Add' => 'Додај',
        'View' => 'Прикажи',
        'Active' => 'Активни',
        'Inactive' => 'Неактивни',
        'Status' => 'Статус',
        'Actions' => 'Акције',
        'Name' => 'Назив',
        'Price' => 'Цена',
        'Total' => 'Укупно',
        
        // Package Related
        'Configure Your Package' => 'Подеси Свој Пакет',
        'Select Your Products' => 'Изабери Своје Производе',
        'Package Summary' => 'Резиме Пакета',
        'Add to Cart' => 'Додај у Корпу',
        'Continue' => 'Настави',
        'Back' => 'Назад',
        'Select' => 'Изабери',
        'Remove' => 'Уклони',
        'Clear All' => 'Обриши Све',
        
        // VIP Related
        'VIP Member' => 'VIP Члан',
        'VIP Price' => 'VIP Цена',
        'Regular Price' => 'Редовна Цена',
        'VIP Discount' => 'VIP Попуст',
        'Regular Discount' => 'Редован Попуст',
        'VIP Benefits' => 'VIP Погодности',
        
        // Messages
        'Loading...' => 'Учитавам...',
        'Please wait...' => 'Молимо сачекај...',
        'Success!' => 'Успех!',
        'Error' => 'Грешка',
        'Warning' => 'Упозорење',
        'Information' => 'Информација',
        
        // Form Elements
        'Email' => 'Имејл',
        'Code' => 'Код',
        'Password' => 'Лозинка',
        'Username' => 'Корисничко име',
        'Phone' => 'Телефон',
        'Address' => 'Адреса',
        'City' => 'Град',
        'Country' => 'Држава',
        
        // E-commerce
        'Cart' => 'Корпа',
        'Checkout' => 'Наплата',
        'Order' => 'Наруџба',
        'Product' => 'Производ',
        'Category' => 'Категорија',
        'Discount' => 'Попуст',
        'Coupon' => 'Купон',
        'Tax' => 'Порез',
        'Shipping' => 'Достава',
        'Payment' => 'Плаћање',
        
        // Time related
        'Today' => 'Данас',
        'Yesterday' => 'Јуче',
        'Tomorrow' => 'Сутра',
        'Week' => 'Недеља',
        'Month' => 'Месец',
        'Year' => 'Година',
        'Date' => 'Датум',
        'Time' => 'Време',
        
        // Numbers and quantities
        'One' => 'Један',
        'Two' => 'Два',
        'Three' => 'Три',
        'Few' => 'Неколико',
        'Many' => 'Много',
        'All' => 'Све',
        'None' => 'Ништа',
        'Item' => 'Ставка',
        'Items' => 'Ставке',
    );
    
    return isset($translations[$text]) ? $translations[$text] : $text;
}

/**
 * Echo Serbian translation
 * @param string $text English text to translate
 */
function wvp_e($text) {
    echo wvp_translate($text);
}

/**
 * Get Serbian translation with sprintf formatting
 * @param string $text Text with placeholders
 * @param mixed ...$args Arguments for sprintf
 * @return string Formatted Serbian translation
 */
function wvp_sprintf($text, ...$args) {
    return sprintf(wvp_translate($text), ...$args);
}