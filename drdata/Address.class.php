<?php

/**
 *  Клас 'drdata_Address' функции за работа с адреси
 *
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class drdata_Address
{
    static $places = array (
			"aitos" => "Айтос",
			"alfatar" => "Алфатар",
			"alphatar" => "Алфатар",
			"arbanasi" => "Арбанаси",
			"arbanassi" => "Арбанаси",
			"asenovgrad" => "Асеновград",
			"aytos" => "Айтос",
			"b slatina" => "Бяла слатина", 
			"balchik" => "Балчик",
			"bansko" => "Банско",
			"belene" => "Белене",
			"belogradchik" => "Белоградчик",
			"berkovica" => "Берковица",
			"berkovitsa" => "Берковица",
			"biala slatina" => "Бяла слатина", 
			"biala" => "Бяла",
			"blagoevgrad" => "Благоевград",
			"botevgrad" => "Ботевград",
			"bourgas" => "Бургас",
			"bqla slatina" => "Бяла слатина",
			"bs" => "Бургас",
			"burgas" => "Бургас",
			"byala slatina" => "Бяла слатина", 
			"byala" => "Бяла",
			"chepelare" => "Чепеларе",
			"cherven briag" => "Червен бряг",
			"cherven bryag" => "Червен бряг", 
			"chiprovtsi" => "Чипровци",
			"chirpan" => "Чирпан", 
			"devin" => "Девин", 
			"dimitrovgrad" => "Димитровград", 
			"dobrich" => "Добрич", 
			"dryanovo" => "Дряново", 
			"dupnica" => "Дупница", 
			"dupnitsa" => "Дупница", 
			"dzhebel" => "Джебел",
			"elena" => "Елена",
			"elhovo" => "Елхово", 
			"etropole" => "Етрополе", 
			"g delchev" => "Гоце Делчев",
			"g oriahovitsa" => "Г. Оряховица", 
			"g oryahovitsa" => "Г. Оряховица", 
			"g toshevo" => "Генерал Тошево",
			"gabrovo " => "Габрово", 
			"gen toshevo" => "Генерал Тошево",
			"general toshevo" => "Генерал Тошево",
			"glavnica" => "Главница",
			"glavnitsa" => "Главница",
			"glavniza" => "Главница",
			"goce delchev" => "Гоце Делчев",
			"gorna oriahovitsa" => "Г. Оряховица", 
			"gorna oryahovitsa" => "Г. Оряховица", 
			"gotse delchev" => "Гоце Делчев",
			"hackovo" => "Хасково",
			"harmanli" => "Харманли", 
			"haskovo" => "Хасково", 
			"iakoruda" => "Якоруда",
			"ihtiman" => "Ихтиман", 
			"isperih" => "Исперих", 
			"ivailovgrad" => "Ивайловград",
			"ivaylovgrad" => "Ивайловград",
			"jakoruda" => "Якоруда",
			"jambol" => "Ямбол", 
			"kameno" => "Камено",
			"kardjali" => "Кърджали", 
			"kardzhali" => "Кърджали", 
			"karlovo" => "Карлово", 
			"karnobat" => "Карнобат", 
			"kavarna" => "Каварна", 
			"kazanlak" => "Казанлък", 
			"kn" => "Казанлък", 
			"kneja" => "Кнежа", 
			"koprivshtitsa" => "Копривщица", 
			"kostinbrod" => "Костинброд", 
			"kotel" => "Котел",
			"kozlodui" => "Козлудуй", 
			"kozloduy" => "Козлудуй", 
			"krumovgrad" => "Крумовград", 
			"kubrat" => "Кубрат", 
			"kula" => "Кула",
			"kustendil" => "Кюстендил",
			"kyustendil" => "Кюстендил",
			"levski" => "Левски", 
			"liaskovets" => "Лясковец", 
			"lom" => "Лом", 
			"london" => "Лондон", 
			"lovech" => "Ловеч", 
			"lukovit" => "Луковит", 
			"lyaskovets" => "Лясковец", 
			"madan" => "Мадан",
			"madrid" => "Мадрид", 
			"mezdra" => "Мездра", 
			"montana" => "Монтана", 
			"n zagora" => "Нова загора", 
			"nesebar" => "Несебър",
			"nesebur" => "Несебър", 
			"nova zagora" => "Нова загора", 
			"oriahovo" => "Оряхово", 
			"oryahovo" => "Оряхово", 
			"p trambesh" => "П. Трамбеш",
			"panagyurishte" => "Панагюрище", 
			"paris" => "Париж", 
			"parvomai" => "Първомай",
			"parvomaj" => "Първомай",
			"parvomay" => "Първомай",
			"pavlikeni" => "Павликени", 
			"pazardjik" => "Пазарджик",
			"pazardzhik" => "Пазарджик",
			"pernik" => "Перник", 
			"peshtera" => "Пещера", 
			"petrich" => "Петрич", 
			"pirdop" => "Пирдоп", 
			"pld" => "Пловдив", 
			"pleven" => "Плевен", 
			"plovdiv" => "Пловдив",
			"polski trambesh" => "П. Трамбеш",
			"pomorie" => "Поморие", 
			"popovo" => "Попово", 
			"preslav" => "Велики преслав", 
			"pz" => "Пазарджик", 
			"radnevo" => "Раднево",
			"radomir" => "Радомир", 
			"rakovski" => "Раковски", 
			"razgrad" => "Разград", 
			"razlog" => "Разлог", 
			"roman" => "Роман", 
			"rousse" => "Русе",
			"rs" => "Русе", 
			"ruse" => "Русе",
			"russe" => "Русе",
			"samokov" => "Самоков", 
			"sandanski" => "Сандански", 
			"sf" => "София",
			"shoumen" => "Шумен", 
			"shumen" => "Шумен",
			"silistra" => "Силистра",
			"simitli" => "Симитли",
			"sliven" => "Сливен", 
			"smolian" => "Смолян",
			"smolyan" => "Смолян", 
			"sofia" => "София",
			"sofiq" => "София", 
			"sofya" => "София", 
			"sopot" => "Сопот", 
			"sozopol" => "Созопол", 
			"st zagora" => "Ст. Загора",
			"stara zagora" => "Ст. Загора",
			"straldzha" => "Стралджа",
			"stralja" => "Стралджа",
			"stz" => "Ст. Загора", 
			"svilengrad" => "Свиленград", 
			"svishtov" => "Свищов", 
			"svoge" => "Своге", 
			"sylistra" => "Силистра", 
			"targovishte" => "Търовище", 
			"tervel" => "Тервел",
			"teteven" => "Тетевен", 
			"troian" => "Троян",
			"troyan" => "Троян", 
			"tsarevo" => "Царево",
			"tutrakan" => "Тутракан", 
			"tvarditsa" => "Твърдица",
			"v preslav" => "Велики преслав", 
			"v tarnovo" => "В. Търново", 
			"v tyrnovo" => "В. Търново",
        	"tarnovo" => "В. Търново", 
			"tyrnovo" => "В. Търново",
			"valencia" => "Валенсия", 
			"varna" => "Варна",
			"veliki preslav" => "Велики преслав", 
			"veliko tarnovo" => "В. Търново",
			"veliko turnovo" => "В. Търново",
			"veliko tyrnovo" => "В. Търново",
			"velingrad" => "Велинград", 
			"vidin" => "Видин", 
			"vn" => "Варна", 
			"vraca" => "Враца",
			"vratsa" => "Враца",
			"vratza" => "Враца", 
			"vt" => "В. Търново", 
			"xackovo" => "Хасково",
			"xaskovo" => "Хасково",
			"yakoruda" => "Якоруда",
			"yambol" => "Ямбол", 
			"zarevo" => "Царево", 
			"zlatica" => "Златица",
			"zlatitsa" => "Златица",
			"zlatograd" => "Златоград" 
	);


    /**
     * Връща добре форматирано име на бг населено място
     */
    function canonizePlace($place)
    {
        $place = mb_convert_case( mb_strtolower($place), MB_CASE_TITLE, "UTF-8");
		$place = str_replace("Гр.","", $place); 
		$place = str_replace("Гр ","", $place); 
        $place = trim($place);

        $placeL = strtolower(STR::utf2ascii($place));
 		$placeL = trim(preg_replace('/[^a-zа-я]+/u', ' ', $placeL));
		$placeL = str_replace("gr ","", $placeL); 
        
        return self::$places[$placeL] ? self::$places[$placeL] : $place;
    }

 
 
}