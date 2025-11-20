<?php
class ProfanityFilter {
    private static $badWords = [
        'fuck', 'shit', 'asshole', 'bitch', 'cunt', 'dick', 'pussy', 'cock', 'whore', 'slut',
        'bastard', 'dickhead', 'piss', 'crap', 'damn', 'fag', 'faggot', 'retard', 'nigger',
        'nigga', 'chink', 'spic', 'kike', 'coon', 'twat', 'wanker', 'bollocks', 'arsehole',
        'bloody', 'bugger', 'cow', 'crikey', 'cunt', 'minge', 'prick', 'pissed', 'pissed off',
        'piss off', 'shitty', 'shite', 'twat', 'wank', 'wanker', 'whore',
        'puta', 'putang ina', 'putangina', 'puta ina', 'gago', 'gaga', 'bobo', 'tanga', 'ulol',
        'bobo', 'bubu', 'bubuwit', 'burat', 'puking ina', 'pukina', 'pota', 'potang ina',
        'potangina', 'tanga', 'tarantado', 'ulol', 'unggoy', 'yawa', 'yawwa', 'leche', 'lintik',
        'pakshet', 'pakyu', 'peste', 'pukinang ina', 'puta ka', 'putang ina mo', 'shet', 'sira ulo',
        'suso mo', 'tamod', 'tanga', 'tanga ka', 'tarantado', 'timang', 'tite', 'tungaw', 'ugok',
        'ulol', 'ungas', 'ungas', 'ungas ka', 'yawa', 'yawa ka'
    ];

    public static function hasProfanity($text) {
        if (empty($text)) {
            return false;
        }

        $text = strtolower($text);
        
        foreach (self::$badWords as $word) {
            if (preg_match("/\b" . preg_quote($word, '/') . "\b/i", $text)) {
                return true;
            }
        }
        
        return false;
    }

    public static function filter($text, $replacement = '*') {
        if (empty($text)) {
            return $text;
        }

        foreach (self::$badWords as $word) {
            $pattern = "/\b" . preg_quote($word, '/') . "\b/i";
            $replacementStr = str_repeat($replacement, strlen($word));
            $text = preg_replace($pattern, $replacementStr, $text);
        }
        
        return $text;
    }

    public static function addBadWords(array $words) {
        self::$badWords = array_merge(self::$badWords, $words);
        self::$badWords = array_unique(self::$badWords);
    }

    public static function getBadWords() {
        return self::$badWords;
    }
}
