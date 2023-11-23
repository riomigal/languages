<?php

namespace Riomigal\Languages\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Riomigal\Languages\Notifications\FlashMessage;

/**
 * @mixin Builder
 */
class Language extends Model
{
    /**
     * Predefined languages
     */
    const LANGUAGES = array(
        array("name" => "Afrikaans", "code" => "af", "native_name" => "Afrikaans"),
        array("name" => "Albanian", "code" => "sq", "native_name" => "shqip"),
        array("name" => "Amharic", "code" => "am", "native_name" => "አማርኛ"),
        array("name" => "Arabic", "code" => "ar", "native_name" => "العربية"),
        array("name" => "Aragonese", "code" => "an", "native_name" => "aragonés"),
        array("name" => "Armenian", "code" => "hy", "native_name" => "հայերեն"),
        array("name" => "Asturian", "code" => "ast", "native_name" => "asturianu"),
        array("name" => "Azerbaijani", "code" => "az", "native_name" => "azərbaycan dili"),
        array("name" => "Basque", "code" => "eu", "native_name" => "euskara"),
        array("name" => "Belarusian", "code" => "be", "native_name" => "беларуская"),
        array("name" => "Bengali", "code" => "bn", "native_name" => "বাংলা"),
        array("name" => "Bosnian", "code" => "bs", "native_name" => "bosanski"),
        array("name" => "Breton", "code" => "br", "native_name" => "brezhoneg"),
        array("name" => "Bulgarian", "code" => "bg", "native_name" => "български"),
        array("name" => "Catalan", "code" => "ca", "native_name" => "català"),
        array("name" => "Central Kurdish", "code" => "ckb", "native_name" => "کوردی (دەستنوسی عەرەبی)"),
        array("name" => "Chinese", "code" => "zh", "native_name" => "中文"),
        array("name" => "Chinese (Hong Kong)", "code" => "zh-HK", "native_name" => "中文（香港）"),
        array("name" => "Chinese (Simplified)", "code" => "zh-CN", "native_name" => "中文（简体）"),
        array("name" => "Chinese (Traditional)", "code" => "zh-TW", "native_name" => "中文（繁體）"),
        array("name" => "Corsican", "code" => "co", "native_name" => "Corsican"),
        array("name" => "Croatian", "code" => "hr", "native_name" => "hrvatski"),
        array("name" => "Czech", "code" => "cs", "native_name" => "čeština"),
        array("name" => "Danish", "code" => "da", "native_name" => "dansk"),
        array("name" => "Dutch", "code" => "nl", "native_name" => "Nederlands"),
        array("name" => "English", "code" => "en", "native_name" => "English"),
        array("name" => "English (Australia)", "code" => "en-AU", "native_name" => "English (Australia)"),
        array("name" => "English (Canada)", "code" => "en-CA", "native_name" => "English (Canada)"),
        array("name" => "English (India)", "code" => "en-IN", "native_name" => "English (India)"),
        array("name" => "English (New Zealand)", "code" => "en-NZ", "native_name" => "English (New Zealand)"),
        array("name" => "English (South Africa)", "code" => "en-ZA", "native_name" => "English (South Africa)"),
        array("name" => "English (United Kingdom)", "code" => "en-GB", "native_name" => "English (United Kingdom)"),
        array("name" => "English (United States)", "code" => "en-US", "native_name" => "English (United States)"),
        array("name" => "Esperanto", "code" => "eo", "native_name" => "esperanto"),
        array("name" => "Estonian", "code" => "et", "native_name" => "eesti"),
        array("name" => "Faroese", "code" => "fo", "native_name" => "føroyskt"),
        array("name" => "Filipino", "code" => "fil", "native_name" => "Filipino"),
        array("name" => "Finnish", "code" => "fi", "native_name" => "suomi"),
        array("name" => "French", "code" => "fr", "native_name" => "français"),
        array("name" => "French (Canada)", "code" => "fr-CA", "native_name" => "français (Canada)"),
        array("name" => "French (France)", "code" => "fr-FR", "native_name" => "français (France)"),
        array("name" => "French (Switzerland)", "code" => "fr-CH", "native_name" => "français (Suisse)"),
        array("name" => "Galician", "code" => "gl", "native_name" => "galego"),
        array("name" => "Georgian", "code" => "ka", "native_name" => "ქართული"),
        array("name" => "German", "code" => "de", "native_name" => "Deutsch"),
        array("name" => "German (Austria)", "code" => "de-AT", "native_name" => "Deutsch (Österreich)"),
        array("name" => "German (Germany)", "code" => "de-DE", "native_name" => "Deutsch (Deutschland)"),
        array("name" => "German (Liechtenstein)", "code" => "de-LI", "native_name" => "Deutsch (Liechtenstein)"),
        array("name" => "German (Switzerland)", "code" => "de-CH", "native_name" => "Deutsch (Schweiz)"),
        array("name" => "Greek", "code" => "el", "native_name" => "Ελληνικά"),
        array("name" => "Guarani", "code" => "gn", "native_name" => "Guarani"),
        array("name" => "Gujarati", "code" => "gu", "native_name" => "ગુજરાતી"),
        array("name" => "Hausa", "code" => "ha", "native_name" => "Hausa"),
        array("name" => "Hawaiian", "code" => "haw", "native_name" => "ʻŌlelo Hawaiʻi"),
        array("name" => "Hebrew", "code" => "he", "native_name" => "עברית"),
        array("name" => "Hindi", "code" => "hi", "native_name" => "हिन्दी"),
        array("name" => "Hungarian", "code" => "hu", "native_name" => "magyar"),
        array("name" => "Icelandic", "code" => "is", "native_name" => "íslenska"),
        array("name" => "Indonesian", "code" => "id", "native_name" => "Indonesia"),
        array("name" => "Interlingua", "code" => "ia", "native_name" => "Interlingua"),
        array("name" => "Irish", "code" => "ga", "native_name" => "Gaeilge"),
        array("name" => "Italian", "code" => "it", "native_name" => "italiano"),
        array("name" => "Italian (Italy)", "code" => "it-IT", "native_name" => "italiano (Italia)"),
        array("name" => "Italian (Switzerland)", "code" => "it-CH", "native_name" => "italiano (Svizzera)"),
        array("name" => "Japanese", "code" => "ja", "native_name" => "日本語"),
        array("name" => "Kannada", "code" => "kn", "native_name" => "ಕನ್ನಡ"),
        array("name" => "Kazakh", "code" => "kk", "native_name" => "қазақ тілі"),
        array("name" => "Khmer", "code" => "km", "native_name" => "ខ្មែរ"),
        array("name" => "Korean", "code" => "ko", "native_name" => "한국어"),
        array("name" => "Kurdish", "code" => "ku", "native_name" => "Kurdî"),
        array("name" => "Kyrgyz", "code" => "ky", "native_name" => "кыргызча"),
        array("name" => "Lao", "code" => "lo", "native_name" => "ລາວ"),
        array("name" => "Latin", "code" => "la", "native_name" => "Latin"),
        array("name" => "Latvian", "code" => "lv", "native_name" => "latviešu"),
        array("name" => "Lingala", "code" => "ln", "native_name" => "lingála"),
        array("name" => "Lithuanian", "code" => "lt", "native_name" => "lietuvių"),
        array("name" => "Macedonian", "code" => "mk", "native_name" => "македонски"),
        array("name" => "Malay", "code" => "ms", "native_name" => "Bahasa Melayu"),
        array("name" => "Malayalam", "code" => "ml", "native_name" => "മലയാളം"),
        array("name" => "Maltese", "code" => "mt", "native_name" => "Malti"),
        array("name" => "Marathi", "code" => "mr", "native_name" => "मराठी"),
        array("name" => "Mongolian", "code" => "mn", "native_name" => "монгол"),
        array("name" => "Nepali", "code" => "ne", "native_name" => "नेपाली"),
        array("name" => "Norwegian", "code" => "no", "native_name" => "norsk"),
        array("name" => "Norwegian Bokmål", "code" => "nb", "native_name" => "norsk bokmål"),
        array("name" => "Norwegian Nynorsk", "code" => "nn", "native_name" => "nynorsk"),
        array("name" => "Occitan", "code" => "oc", "native_name" => "Occitan"),
        array("name" => "Oriya", "code" => "or", "native_name" => "ଓଡ଼ିଆ"),
        array("name" => "Oromo", "code" => "om", "native_name" => "Oromoo"),
        array("name" => "Pashto", "code" => "ps", "native_name" => "پښتو"),
        array("name" => "Persian", "code" => "fa", "native_name" => "فارسی"),
        array("name" => "Polish", "code" => "pl", "native_name" => "polski"),
        array("name" => "Portuguese", "code" => "pt", "native_name" => "português"),
        array("name" => "Portuguese (Brazil)", "code" => "pt-BR", "native_name" => "português (Brasil)"),
        array("name" => "Portuguese (Portugal)", "code" => "pt-PT", "native_name" => "português (Portugal)"),
        array("name" => "Punjabi", "code" => "pa", "native_name" => "ਪੰਜਾਬੀ"),
        array("name" => "Quechua", "code" => "qu", "native_name" => "Quechua"),
        array("name" => "Romanian", "code" => "ro", "native_name" => "română"),
        array("name" => "Romanian (Moldova)", "code" => "mo", "native_name" => "română (Moldova)"),
        array("name" => "Romansh", "code" => "rm", "native_name" => "rumantsch"),
        array("name" => "Russian", "code" => "ru", "native_name" => "русский"),
        array("name" => "Scottish Gaelic", "code" => "gd", "native_name" => "Scottish Gaelic"),
        array("name" => "Serbian", "code" => "sr", "native_name" => "српски"),
        array("name" => "Serbo", "code" => "sh", "native_name" => "Croatian"),
        array("name" => "Shona", "code" => "sn", "native_name" => "chiShona"),
        array("name" => "Sindhi", "code" => "sd", "native_name" => "Sindhi"),
        array("name" => "Sinhala", "code" => "si", "native_name" => "සිංහල"),
        array("name" => "Slovak", "code" => "sk", "native_name" => "slovenčina"),
        array("name" => "Slovenian", "code" => "sl", "native_name" => "slovenščina"),
        array("name" => "Somali", "code" => "so", "native_name" => "Soomaali"),
        array("name" => "Southern Sotho", "code" => "st", "native_name" => "Southern Sotho"),
        array("name" => "Spanish", "code" => "es", "native_name" => "español"),
        array("name" => "Spanish (Argentina)", "code" => "es-AR", "native_name" => "español (Argentina)"),
        array("name" => "Spanish (Latin America)", "code" => "es-419", "native_name" => "español (Latinoamérica)"),
        array("name" => "Spanish (Mexico)", "code" => "es-MX", "native_name" => "español (México)"),
        array("name" => "Spanish (Spain)", "code" => "es-ES", "native_name" => "español (España)"),
        array("name" => "Spanish (United States)", "code" => "es-US", "native_name" => "español (Estados Unidos)"),
        array("name" => "Sundanese", "code" => "su", "native_name" => "Sundanese"),
        array("name" => "Swahili", "code" => "sw", "native_name" => "Kiswahili"),
        array("name" => "Swedish", "code" => "sv", "native_name" => "svenska"),
        array("name" => "Tajik", "code" => "tg", "native_name" => "тоҷикӣ"),
        array("name" => "Tamil", "code" => "ta", "native_name" => "தமிழ்"),
        array("name" => "Tatar", "code" => "tt", "native_name" => "Tatar"),
        array("name" => "Telugu", "code" => "te", "native_name" => "తెలుగు"),
        array("name" => "Thai", "code" => "th", "native_name" => "ไทย"),
        array("name" => "Tigrinya", "code" => "ti", "native_name" => "ትግርኛ"),
        array("name" => "Tongan", "code" => "to", "native_name" => "lea fakatonga"),
        array("name" => "Turkish", "code" => "tr", "native_name" => "Türkçe"),
        array("name" => "Turkmen", "code" => "tk", "native_name" => "Turkmen"),
        array("name" => "Twi", "code" => "tw", "native_name" => "Twi"),
        array("name" => "Ukrainian", "code" => "uk", "native_name" => "українська"),
        array("name" => "Urdu", "code" => "ur", "native_name" => "اردو"),
        array("name" => "Uyghur", "code" => "ug", "native_name" => "Uyghur"),
        array("name" => "Uzbek", "code" => "uz", "native_name" => "o‘zbek"),
        array("name" => "Vietnamese", "code" => "vi", "native_name" => "Tiếng Việt"),
        array("name" => "Walloon", "code" => "wa", "native_name" => "wa"),
        array("name" => "Welsh", "code" => "cy", "native_name" => "Cymraeg"),
        array("name" => "Western Frisian", "code" => "fy", "native_name" => "Western Frisian"),
        array("name" => "Xhosa", "code" => "xh", "native_name" => "Xhosa"),
        array("name" => "Yiddish", "code" => "yi", "native_name" => "Yiddish"),
        array("name" => "Yoruba", "code" => "yo", "native_name" => "Èdè Yorùbá"),
        array("name" => "Zulu", "code" => "zu", "native_name" => "isiZulu")
    );

    /**
     * @var string[]
     */
    protected $fillable = [
        'name', 'code', 'native_name'
    ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->table = config('languages.table_languages');
        $this->connection = config('languages.db_connection');
        parent::__construct($attributes);
    }

    /**
     * @return BelongsToMany
     */
    public function translators(): BelongsToMany
    {
        return $this->belongsToMany(Translator::class, config('languages.table_translator_language'));
    }

    /**
     * @return HasMany
     */
    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class);
    }

}
