
<?php


/**
 * Клас  'tests_Test' - Разни тестове на PHP-to
 *
 * @category  bgerp
 * @package   tests
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class unit_MinkPbgERP extends core_Manager {
   
   /** Номерацията показва препоръчвания ред на изпълнение. Еднаквите номера могат да се разместват.
    *return  $browser->getHtml();
    */
  
    /**
     * 16. Нова покупка от съществуваща фирма с папка
     * Проверка състояние чакащо плащане - надплатено
     */
     
    //http://localhost/unit_MinkPbgERP/CreatePurchaseOverpaid/
    function act_CreatePurchaseOverpaid()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
    
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
    
        // нова покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
         
        //$browser->setValue('bankAccountId', 1);
        $valior=strtotime("-2 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('note', 'MinkPTestCreatePurchaseOverpaid');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        $browser->press('Чернова');
    
        // Записваме черновата на покупката
         
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'. Ако е с дес. запетая - също апостроф.
       
        $browser->press('Артикул');
        $browser->setValue('productId', '7');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100-06*08');//52
        $browser->setValue('packPrice', '0,0100+3*0,8');//2,41
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
             
        // Записваме артикула и добавяме нов
    
        $browser->setValue('productId', '5');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100-03*8');//76
        $browser->setValue('packPrice', '010,020+0,3*08');//12.6
        $browser->setValue('discount', 2);
             
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', '9');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 01,00);
        $browser->setValue('packPrice', '1,0202');
        $browser->setValue('discount', 1);
             
        // Записваме артикула
        $browser->press('Запис');
        // активираме покупката
        $browser->press('Активиране');
             
        if(strpos($browser->gettext(), '209,52')) {
        } else {
            return "Грешно ДДС";
        }
    
        if(strpos($browser->gettext(), 'Хиляда двеста петдесет и седем BGN и 0,13')) {
        } else {
            return "Грешна обща сума";
        }
    
        // Складова разписка
        $browser->press('Засклаждане');
        $browser->setValue('storeId', 1);
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // протокол
        $browser->press('Приемане');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Контиране')) {
        //  $browser->press('Контиране');
        //}
    
        // Фактура
        $browser->press('Вх. фактура');
        $browser->setValue('number', '1783');
        $browser->setValue('date', date('d-m-Y', $valior));
        $valior=strtotime("+1 Day");
        $browser->setValue('dueDate', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // РКО
        $browser->press('РКО');
        $browser->setValue('beneficiary', 'Иван Петров');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', '1');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // РБД
        $browser->press('РБД');
        $browser->setValue('ownAccount', 'BG21 CREX 9260 3114 5487 01');
        $browser->setValue('amountDeal', '01251,36');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Проверка Чакащо плащане
        if(strpos($browser->gettext(), 'Чакащо плащане: Не')) {
        } else {
            return "Грешно чакащо плащане";
        }
    }
    
    /**
     * 16. Нова покупка от съществуваща фирма с папка
     * Проверка състояние чакащо плащане - да
     */
     
    //http://localhost/unit_MinkPbgERP/CreatePurchaseWait/
    function act_CreatePurchaseWait()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
    
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
    
        // нова покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
         
        //$browser->setValue('bankAccountId', 1);
        $valior=strtotime("-2 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('note', 'MinkPTestCreatePurchaseWait');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        $browser->press('Чернова');
    
        // Записваме черновата на покупката
         
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'. Ако е с дес. запетая - също апостроф.
    
            $browser->press('Артикул');
            $browser->setValue('productId', '7');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', '004+03*08');//28
            $browser->setValue('packPrice', '010,2');//10.2
            $browser->setValue('discount', 3);
            $browser->press('Запис и Нов');
             
            // Записваме артикула и добавяме нов
    
            $browser->setValue('productId', '5');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', '080-07*8');//24
            $browser->setValue('packPrice', '01,20+0,3*08');//3,6
            $browser->setValue('discount', 2);
             
            // Записваме артикула и добавяме нов - услуга
            $browser->press('Запис и Нов');
            $browser->setValue('productId', '9');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', '017');
            $browser->setValue('packPrice', '1.07');
            $browser->setValue('discount', '10.02');
             
            // Записваме артикула
            $browser->press('Запис');
    
            // активираме покупката
            $browser->press('Активиране');
            //return  $browser->getHtml();
            //$browser->press('Активиране/Контиране');
             
            if(strpos($browser->gettext(), '75,61')) {
            } else {
                return "Грешно ДДС";
            }
    
            if(strpos($browser->gettext(), 'Четиристотин петдесет и три BGN и 0,68')) {
            } else {
                return "Грешна обща сума";
            }
    
            // Складова разписка
            $browser->press('Засклаждане');
            $browser->setValue('storeId', 1);
            $browser->setValue('valior', date('d-m-Y', $valior));
            $browser->press('Чернова');
            $browser->press('Контиране');
    
            // протокол
            $browser->press('Приемане');
            $browser->setValue('valior', date('d-m-Y', $valior));
            $browser->press('Чернова');
            $browser->press('Контиране');
            //if(strpos($browser->gettext(), 'Контиране')) {
            //  $browser->press('Контиране');
            //}
    
            // Фактура
            $browser->press('Вх. фактура');
            $browser->setValue('number', '7819');
            $browser->setValue('date', date('d-m-Y', $valior));
            $valior=strtotime("+1 Day");
            $browser->setValue('dueDate', date('d-m-Y', $valior));
            $browser->press('Чернова');
            $browser->press('Контиране');
    
            // РКО
            $browser->press('РКО');
            $browser->setValue('beneficiary', 'Иван Петров');
            $browser->setValue('amountDeal', '100');
            $browser->setValue('peroCase', '1');
            $browser->press('Чернова');
            $browser->press('Контиране');
    
            // РБД
            $browser->press('РБД');
            $browser->setValue('ownAccount', 'BG21 CREX 9260 3114 5487 01');
            $browser->setValue('amountDeal', '0126,36');
            $browser->press('Чернова');
            $browser->press('Контиране');
    
            // Проверка Чакащо плащане
            if(strpos($browser->gettext(), 'Чакащо плащане: Да')) {
            } else {
                return "Грешно чакащо плащане";
            }
    }
    /**
     * 16. Нова покупка от съществуваща фирма с папка
     * Проверка състояние чакащо плащане - просрочено
     */
     
    //http://localhost/unit_MinkPbgERP/CreatePurchaseOverdue/
    function act_CreatePurchaseOverdue()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
    
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
    
        // нова покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
         
        //$browser->setValue('bankAccountId', 1);
        $valior=strtotime("-4 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('note', 'MinkPTestCreatePurchaseOverdue');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        $browser->press('Чернова');
    
        // Записваме черновата на покупката
         
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        
        $browser->press('Артикул');
        $browser->setValue('productId', '7');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010+03*08');//34
        $browser->setValue('packPrice', '010+3*0.8');//12.4
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
         
        // Записваме артикула и добавяме нов
        
        $browser->setValue('productId', '5');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100-09*8');//28
        $browser->setValue('packPrice', '08,20+0.3*08');//10.6
        $browser->setValue('discount', 5);
         
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', '9');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 107);
        $browser->setValue('packPrice', '0,027');
        //$browser->setValue('discount', 2);
         
        // Записваме артикула
        $browser->press('Запис');
        
        // активираме покупката
        $browser->press('Активиране');
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), '138,76')) {
        } else {
            return "Грешно ДДС";
        }
        
        if(strpos($browser->gettext(), 'Осемстотин тридесет и два BGN и 0,56')) {
        } else {
            return "Грешна обща сума";
        }
        
        // Складова разписка
            $browser->press('Засклаждане');
            $browser->setValue('storeId', 1);
            $browser->setValue('valior', date('d-m-Y', $valior));
            $browser->press('Чернова');
            $browser->press('Контиране');
    
            // протокол
            $browser->press('Приемане');
            $browser->setValue('valior', date('d-m-Y', $valior));
            $browser->press('Чернова');
            $browser->press('Контиране');
            //if(strpos($browser->gettext(), 'Контиране')) {
            //  $browser->press('Контиране');
            //}
    
            // Фактура
            $browser->press('Вх. фактура');
            $browser->setValue('number', '1722');
            $browser->setValue('date', date('d-m-Y', $valior));
            $valior=strtotime("-1 Day");
            $browser->setValue('dueDate', date('d-m-Y', $valior));
            $browser->press('Чернова');
            $browser->press('Контиране');
    
            // РКО
            $browser->press('РКО');
            $browser->setValue('beneficiary', 'Иван Петров');
            $browser->setValue('valior', date('d-m-Y', $valior));
            $browser->setValue('amountDeal', '100');
            $browser->setValue('peroCase', '1');
            $browser->press('Чернова');
            $browser->press('Контиране');
    
            // РБД
            $browser->press('РБД');
            $browser->setValue('ownAccount', 'BG21 CREX 9260 3114 5487 01');
            $browser->setValue('amountDeal', '0126,36');
            $browser->setValue('valior', date('d-m-Y', $valior));
            $browser->press('Чернова');
            $browser->press('Контиране');
            
            // Проверка Чакащо плащане
            if(strpos($browser->gettext(), 'Чакащо плащане: Просрочено')) {
            } else {
                return "Грешно чакащо плащане";
            }
    }
    /**
     * 16. Нова покупка от съществуваща фирма с папка
     * Проверка количество и цени - изрази
     * Проверка състояние чакащо плащане - не (платено)
     */
     
    //http://localhost/unit_MinkPbgERP/CreatePurchase/
    function act_CreatePurchase()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
    
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
    
        // нова покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
         
        //$browser->setValue('bankAccountId', 1);
        $browser->setValue('note', 'MinkPTestCreatePurchase');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        $browser->press('Чернова');
    
        // Записваме черновата на покупката
         
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
    
            $browser->press('Артикул');
            $browser->setValue('productId', '7');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', '008+03*08');//32
            $browser->setValue('packPrice', '010+3*0.8');//12.4
            $browser->setValue('discount', 3);
            $browser->press('Запис и Нов');
            //return  $browser->getHtml();
            // Записваме артикула и добавяме нов
            $browser->setValue('productId', '5');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', '0100-07*8');//44
            $browser->setValue('packPrice', '010.20+0.3*08');//12.6
            $browser->setValue('discount', 2);
            $browser->press('Запис и Нов');
            // Записваме артикула и добавяме нов
    
            $browser->setValue('productId', '6');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', '023 + 012*03');//59
            $browser->setValue('packPrice', '091 - 023*02');//45
            $browser->setValue('discount', 4);
    
            // Записваме артикула и добавяме нов - услуга
            $browser->press('Запис и Нов');
            $browser->setValue('productId', '9');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', 113);
            $browser->setValue('packPrice', '1,127');
            $browser->setValue('discount', 5);
    
            // Записваме артикула и добавяме нов - услуга
            $browser->press('Запис и Нов');
            $browser->setValue('productId', '3');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', '1000 / 08-09*08');//48
            $browser->setValue('packPrice', '100/02-3*08');//26
            $browser->setValue('discount', 10);
    
            // Записваме артикула
            $browser->press('Запис');
             
            // активираме покупката
            $browser->press('Активиране');
            //return  $browser->getHtml();
            //$browser->press('Активиране/Контиране');
             
            if(strpos($browser->gettext(), '967,64')) {
            } else {
                return "Грешно ДДС";
            }
    
            if(strpos($browser->gettext(), 'Пет хиляди осемстотин и пет BGN и 0,83')) {
            } else {
                return "Грешна обща сума";
            }
    
            // Складова разписка
            $browser->press('Засклаждане');
            $browser->setValue('storeId', 1);
            $browser->press('Чернова');
            $browser->press('Контиране');
    
            // протокол
            $browser->press('Приемане');
            $browser->press('Чернова');
            $browser->press('Контиране');
            //if(strpos($browser->gettext(), 'Контиране')) {
            //  $browser->press('Контиране');
            //}
    
            // Фактура
            $browser->press('Вх. фактура');
            $browser->setValue('number', '1176');
            $browser->press('Чернова');
            $browser->press('Контиране');
    
            // РКО
            $browser->press('РКО');
            $browser->setValue('beneficiary', 'Иван Петров');
            $browser->setValue('amountDeal', '100');
            $browser->setValue('peroCase', '1');
            $browser->press('Чернова');
            $browser->press('Контиране');
    
            // РБД
            $browser->press('РБД');
            $browser->setValue('ownAccount', 'BG21 CREX 9260 3114 5487 01');
            $browser->press('Чернова');
            $browser->press('Контиране');
    
            $browser->press('Приключване');
            $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
            $browser->press('Чернова');
            $browser->press('Контиране');
            if(strpos($browser->gettext(), 'Чакащо плащане: Не')) {
            } else {
                return "Грешно чакащо плащане";
            }
    }
    /**
     * 15.
     * Проверка състояние плащане - чакащо, метод - на момента
     * Бърза продажба на съществуваща фирма с папка
     */
    //http://localhost/unit_MinkPbgERP/CreateSaleMoment/
    function act_CreateSaleMoment()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
       
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
    
        // нова продажба - проверка има ли бутон
    
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
        
        $valior=strtotime("Now");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('reff', 'Moment');
        //$browser->setValue('bankAccountId', 1);
        $browser->setValue('note', 'MinkPTestCreateSaleMoment');
        $browser->setValue('caseId', 1);
        $browser->setValue('shipmentStoreId', 1);
        $browser->setValue('pricesAtDate', date('d-m-Y'));
        $browser->setValue('paymentMethodId', "На момента");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
    
        $browser->press('Артикул');
        $browser->setValue('productId', '7');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010,0+03*08');//34
        $browser->setValue('packPrice', '01,00+3*0.8');//3.4
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
             
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', '5');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '01,00-03*8');//76
        $browser->setValue('packPrice', '0100.20+0.3*08');//102.6
        $browser->setValue('discount', 2);
    
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', '9');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 18);
        $browser->setValue('packPrice', 1.2117);
        $browser->setValue('discount', 1);
    
        // Записваме артикула
        $browser->press('Запис');
       
        // активираме продажбата
        //$browser->press('Активиране');
        //Контиране на извършени на момента действия (опционално):
        //$browser->setValue('action[ship]','1');
        //return  $browser->getHtml();
////////////////////////////////////        
        $exp='Експедиране на продукти от склад "Склад 1"';
        $browser->setValue($exp,'1');
        $browser->setValue('Прието плащане в брой в каса "КАСА 2"','1');
        $browser->press('Активиране/Контиране');
////////return  $browser->getHtml();
        if(strpos($browser->gettext(), '26,75')) {
        } else {
            return "Грешно ДДС";
        }
    
        if(strpos($browser->gettext(), 'Сто и шестдесет BGN и 0,47')) {
        } else {
            return "Грешна обща сума";
        }
  
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 1);
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // протокол
        $browser->press('Пр. услуги');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
            
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('date', date('d-m-Y', $valior));
        $browser->setValue('numlimit', '2000000 - 3000000');
        //$browser->setValue('numlimit', '0 - 2000000');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', '1');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', 'BG21 CREX 9260 3114 5487 01');
        $browser->setValue('amountDeal', '100');
        $browser->press('Чернова');
        $browser->press('Контиране');
             
        if(strpos($browser->gettext(), 'Чакащо плащане: Да')) {
        } else {
            return "Грешно чакащо плащане";
        }
    }
    /**
     * 14. Нова продажба на съществуваща фирма с папка
     * Проверка количество и цени - изрази
     * Проверка състояние чакащо плащане - не (платено)
     */
     
    //http://localhost/unit_MinkPbgERP/CreateSale/
    
    function act_CreateSale()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
    
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');
        $endhour=strtotime("+5 hours");
        $enddate=strtotime("+1 Day");
         
        $browser->setValue('deliveryTime[d]', date('d-m-Y', $enddate));
        //$browser->setValue('deliveryTime[d]', date('d-m-Y'));
        $browser->setValue('deliveryTime[t]', date('h:i:sa', $endhour));
        //$browser->setValue('deliveryTime[t]', '10:30');
    
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', 1);
        $browser->setValue('note', 'MinkPTestCreateSale');
        $browser->setValue('pricesAtDate', date('d-m-Y'));
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
    
            $browser->press('Артикул');
            $browser->setValue('productId', '7');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', '010+03*08');//34
            $browser->setValue('packPrice', '01+3*0,8');//3.4
            $browser->setValue('discount', 3);
            $browser->press('Запис и Нов');
           
            // Записваме артикула и добавяме нов
            $browser->setValue('productId', '5');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', '03*048-0123');//21
            $browser->setValue('packPrice', '010.20+0.3*08');//12.6
            $browser->setValue('discount', 2);
            $browser->press('Запис и Нов');
            // Записваме артикула и добавяме нов
    
            $browser->setValue('productId', '6');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', '023 + 017*02');//57
            $browser->setValue('packPrice', '091 - 013*02');//65
            $browser->setValue('discount', 3);
    
            // Записваме артикула и добавяме нов - услуга
            $browser->press('Запис и Нов');
            $browser->setValue('productId', '9');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', 114);
            $browser->setValue('packPrice', 1.1124);
            $browser->setValue('discount', 1);
    
            // Записваме артикула и добавяме нов - услуга
            $browser->press('Запис и Нов');
            $browser->setValue('productId', '3');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', '160 / 05-03*08');//8
            $browser->setValue('packPrice', '100/05+3*08');//44
            $browser->setValue('discount', 1);
    
            // Записваме артикула
            $browser->press('Запис');
            // Игнорираме предупреждението за липсваща стока
            //$browser->setValue('Ignore', 1);
            //$browser->press('Запис');
    
            // активираме продажбата
            $browser->press('Активиране');
            //return  $browser->getHtml();
            //$browser->press('Активиране/Контиране');
             
            if(strpos($browser->gettext(), '887,87')) {
            } else {
                return "Грешно ДДС";
            }
    
            if(strpos($browser->gettext(), 'Пет хиляди триста двадесет и седем BGN и 0,18')) {
            } else {
                return "Грешна обща сума";
            }
    
            // експедиционно нареждане
            $browser->press('Експедиране');
            $browser->setValue('storeId', 1);
            $browser->press('Чернова');
            $browser->press('Контиране');
            // тази проверка не работи
            //if(strpos($browser->gettext(), 'Контиране')) {
            //}
            //if(strpos($browser->gettext(), 'Двадесет и седем хиляди осемстотин и осемнадесет')) {
            // връща грешка, ако не е избрано ЕН с цени
            //} else {
            //    return Err3;
            //}
             
            // протокол
            $browser->press('Пр. услуги');
            $browser->press('Чернова');
            $browser->press('Контиране');
            //if(strpos($browser->gettext(), 'Контиране')) {
            //  $browser->press('Контиране');
            //}
    
            // Фактура
            $browser->press('Фактура');
            $browser->press('Чернова');
            //return 'paymentType';
            //$browser->setValue('paymentType', 'По банков път');
    
            $browser->press('Контиране');
    
            // ПКО
            $browser->press('ПКО');
            $browser->setValue('depositor', 'Иван Петров');
            $browser->setValue('amountDeal', '100');
            $browser->setValue('peroCase', '1');
            $browser->press('Чернова');
            $browser->press('Контиране');
    
            // ПБД
            $browser->press('ПБД');
            $browser->setValue('ownAccount', 'BG21 CREX 9260 3114 5487 01');
            //$browser->setValue('amountDeal', '100');
            $browser->press('Чернова');
            $browser->press('Контиране');
    
            // Приключване
            //$browser->selectNode("#Sal89 > td:nth-child(2) > div:nth-child(1) > div:nth-child(1) > input:nth-child(1)");
            //$browser->click();
    
            $browser->press('Приключване');
            $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
            $browser->press('Чернова');
            //return  $browser->getHtml();
            $browser->press('Контиране');
            if(strpos($browser->gettext(), 'Чакащо плащане: Не')) {
            } else {
                return "Грешно чакащо плащане";
            }
    }
    /**
     * 13.
     * Нова продажба на съществуваща фирма с папка
     * Проверка състояние плащане - чакащо, метод - до x дни след фактуриране (3,7,10,15,21,30) в ден преди падеж
     */
    //http://localhost/unit_MinkPbgERP/CreateSaleWait/
    function act_CreateSaleWait()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
    
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
    
        // нова продажба - проверка има ли бутон
    
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        $valior=strtotime("-2 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('reff', 'Wait');
        $browser->setValue('bankAccountId', 1);
        $browser->setValue('note', 'MinkPTestCreateSaleWait');
        $browser->setValue('pricesAtDate', date('d-m-Y', $valior));
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
    
            $browser->press('Артикул');
            $browser->setValue('productId', '7');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', '010+02*09');//28
            $browser->setValue('packPrice', '080-3*0.8');//77,6
            $browser->setValue('discount', 3);
            $browser->press('Запис и Нов');
             
            // Записваме артикула и добавяме нов
            $browser->setValue('productId', '5');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', '0100-05*8');//60
            $browser->setValue('packPrice', '010.21+0.3*08');//12.61
            $browser->setValue('discount', 2);
    
            // Записваме артикула и добавяме нов - услуга
            $browser->press('Запис и Нов');
            $browser->setValue('productId', '9');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', 112);
            $browser->setValue('packPrice', 0.1987);
            $browser->setValue('discount', 1);
    
            // Записваме артикула
            $browser->press('Запис');
    
            // активираме продажбата
            $browser->press('Активиране');
            //$browser->press('Активиране/Контиране');
             
            if(strpos($browser->gettext(), '574,22')) {
            } else {
                return "Грешно ДДС";
            }
    
            if(strpos($browser->gettext(), 'Три хиляди четиристотин четиридесет и пет BGN и 0,34')) {
            } else {
                return "Грешна обща сума";
            }
    
            // експедиционно нареждане
            $browser->press('Експедиране');
            $browser->setValue('valior', date('d-m-Y', $valior));
            $browser->setValue('storeId', 1);
            $browser->press('Чернова');
            $browser->press('Контиране');
    
            // протокол
            $browser->press('Пр. услуги');
            $browser->setValue('valior', date('d-m-Y', $valior));
            $browser->press('Чернова');
            $browser->press('Контиране');
            //if(strpos($browser->gettext(), 'Контиране')) {
            //  $browser->press('Контиране');
            //}
    
            // Фактура
            $browser->press('Фактура');
            $browser->setValue('date', date('d-m-Y', $valior));
            $valior=strtotime("+1 day");
            $browser->setValue('dueDate', date('d-m-Y', $valior));
            //$browser->setValue('numlimit', '2000000 - 3000000');
            $browser->setValue('numlimit', '0 - 2000000');
            $browser->press('Чернова');
            $browser->press('Контиране');
    
            // ПКО
            $browser->press('ПКО');
            $browser->setValue('depositor', 'Иван Петров');
            $browser->setValue('amountDeal', '100');
            $browser->setValue('peroCase', '1');
            $browser->press('Чернова');
            $browser->press('Контиране');
    
            // ПБД
            $browser->press('ПБД');
            $browser->setValue('ownAccount', 'BG21 CREX 9260 3114 5487 01');
            $browser->setValue('amountDeal', '100');
            $browser->press('Чернова');
            $browser->press('Контиране');
             
            if(strpos($browser->gettext(), 'Чакащо плащане: Да')) {
            } else {
                return "Грешно чакащо плащане";
            }
    }
    /**
     * 12. 
     * Проверка състояние плащане - чакащо, метод - на момента, падежът е днес
     * Нова продажба на съществуваща фирма с папка
     */
    //http://localhost/unit_MinkPbgERP/CreateSaleMomentWaitP/
    function act_CreateSaleMomentWaitP()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
                
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
    
        // нова продажба - проверка има ли бутон
    
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
        // -3 Days - за да се обхване случая, когато няма краен срок на плащане на фактурата
        $valior=strtotime("-3 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('reff', 'MomentWaitP');
        $browser->setValue('bankAccountId', 1);
        $browser->setValue('note', 'MinkPTestCreateSaleMomentWaitP');
        $browser->setValue('pricesAtDate', date('d-m-Y'));
        $browser->setValue('paymentMethodId', "На момента");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
    
        $browser->press('Артикул');
        $browser->setValue('productId', '7');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010+03*08');//34
        $browser->setValue('packPrice', '010+3*0.8');//12.4
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
             
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', '5');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100-03*8');//76
        $browser->setValue('packPrice', '010.20+0.3*08');//12.6
        $browser->setValue('discount', 2);
    
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', '9');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 113);
        $browser->setValue('packPrice', 0.2353);
        $browser->setValue('discount', 1);
    
        // Записваме артикула
        $browser->press('Запис');
            
        // активираме продажбата
        $browser->press('Активиране');
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
             
        if(strpos($browser->gettext(), '274,74')) {
        } else {
            return "Грешно ДДС";
        }
    
        if(strpos($browser->gettext(), 'Хиляда шестстотин четиридесет и осем BGN и 0,46')) {
        } else {
            return "Грешна обща сума";
        }
    
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 1);
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // протокол
        $browser->press('Пр. услуги');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Контиране')) {
        //  $browser->press('Контиране');
        //}
    
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('date', date('d-m-Y', $valior));
        //$browser->setValue('dueDate', date('d-m-Y', $valior));
        $browser->setValue('dueDate', null);
        //$browser->setValue('numlimit', '2000000 - 3000000');
        $browser->setValue('numlimit', '0 - 2000000');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', '1');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
                  
        if(strpos($browser->gettext(), 'Чакащо плащане: Да')) {
        } else {
            return "Грешно чакащо плащане";
        }
    }
    
    
    /**
     * 11.
     * Проверка състояние плащане - просрочено, метод - на момента, краен срок - Null
     * Нова продажба на съществуваща фирма с папка
     */
    //http://localhost/unit_MinkPbgERP/CreateSaleMomentOverdueNull/
    function act_CreateSaleMomentOverdueNull()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
    
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
    
        // нова продажба - проверка има ли бутон
    
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
        // -4 Days - за да се обхване случая, когато няма краен срок на плащане на фактурата
        $valior=strtotime("-4 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('reff', 'MomentOverdue');
        $browser->setValue('note', 'MinkPTestCreateSaleMomentOverdue');
        $browser->setValue('pricesAtDate', date('d-m-Y'));
        $browser->setValue('paymentMethodId', "На момента");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
    
            $browser->press('Артикул');
            $browser->setValue('productId', '7');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', '01+03*08');//25
            $browser->setValue('packPrice', '010+3*0.8');//12.4
            $browser->setValue('discount', 3);
            $browser->press('Запис и Нов');
             
            // Записваме артикула и добавяме нов
            $browser->setValue('productId', '5');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', '0100-03*8');//76
            $browser->setValue('packPrice', '010.20-0.3*08');//7.8
            $browser->setValue('discount', 2);
    
            // Записваме артикула и добавяме нов - услуга
            $browser->press('Запис и Нов');
            $browser->setValue('productId', '9');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', 123);
            $browser->setValue('packPrice', 1.121);
            $browser->setValue('discount', 1);
    
            // Записваме артикула
            $browser->press('Запис');
    
            // активираме продажбата
            $browser->press('Активиране');
            //return  $browser->getHtml();
            //$browser->press('Активиране/Контиране');
             
            if(strpos($browser->gettext(), '203,63')) {
            } else {
                return "Грешно ДДС";
            }
    
            if(strpos($browser->gettext(), 'Хиляда двеста двадесет и един BGN и 0,77')) {
            } else {
                return "Грешна обща сума";
            }
    
            // експедиционно нареждане
            $browser->press('Експедиране');
            $browser->setValue('valior', date('d-m-Y', $valior));
            $browser->setValue('storeId', 1);
            $browser->press('Чернова');
            $browser->press('Контиране');
    
            // протокол
            $browser->press('Пр. услуги');
            $browser->setValue('valior', date('d-m-Y', $valior));
            $browser->press('Чернова');
            $browser->press('Контиране');
            //if(strpos($browser->gettext(), 'Контиране')) {
            //  $browser->press('Контиране');
            //}
    
            // Фактура
            $browser->press('Фактура');
            $browser->setValue('date', date('d-m-Y', $valior));
            //$browser->setValue('dueDate', date('d-m-Y', $valior));
            $browser->setValue('dueDate', null);
            //$browser->setValue('numlimit', '2000000 - 3000000');
            $browser->setValue('numlimit', '0 - 2000000');
            $browser->press('Чернова');
            
            $browser->press('Контиране');
    
            // ПКО
            $browser->press('ПКО');
            $browser->setValue('depositor', 'Иван Петров');
            $browser->setValue('amountDeal', '100');
            $browser->setValue('peroCase', '1');
            $browser->setValue('valior', date('d-m-Y', $valior));
            $browser->press('Чернова');
            $browser->press('Контиране');
                   
            if(strpos($browser->gettext(), 'Чакащо плащане: Просрочено')) {
            } else {
                return "Грешно чакащо плащане";
            }
    }       
            
    /**
     * 11.
     * Проверка състояние плащане - надплатено, доставено и нефактурирано
     * Нова продажба на съществуваща фирма с папка -  лв
     */
    //http://localhost/unit_MinkPbgERP/CreateSaleOverpaid/
    function act_CreateSaleOverpaid()
    {
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
    
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');
        $valior=strtotime("-4 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('reff', 'Overpaid');
        $browser->setValue('bankAccountId', 1);
        $browser->setValue('note', 'MinkPbgErpCreateOverpaid');
        $browser->setValue('pricesAtDate', date('d-m-Y', $valior));
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
     
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
    
        $browser->press('Артикул');
        $browser->setValue('productId', '7');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010+03*04');//22
        $browser->setValue('packPrice', '01+3*0.4');//2.2
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
    
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', '5');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '021-03*4');//9
        $browser->setValue('packPrice', '09,28+0,3*04');//10.48
        $browser->setValue('discount', 2);
           
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), '27,88')) {
        } else {
            return "Грешно ДДС";
        }
    
        if(strpos($browser->gettext(), 'Сто шестдесет и седем BGN и 0,26')) {
        } else {
            return "Грешна обща сума";
        }
    
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '70');
        $browser->setValue('peroCase', '1');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', 'BG21 CREX 9260 3114 5487 01');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 1);
        $browser->press('Чернова');
        $browser->press('Контиране');
        //return  $browser->getHtml();
        if(strpos($browser->gettext(), 'Чакащо плащане: Не')) {
        } else {
            return "Грешно чакащо плащане (вярно - Не)";
        }
    
    }
    /**
     * 11.
     * Проверка състояние плащане - просрочено, доставено и нефактурирано
     * Нова продажба на съществуваща фирма с папка -  лв
     */
    //http://localhost/unit_MinkPbgERP/CreateSaleExped1/
    function act_CreateSaleExped1()
    {
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
    
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        $valior=strtotime("-4 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('reff', 'exp1');
        $browser->setValue('bankAccountId', 1);
        $browser->setValue('note', 'MinkPbgErpCreateSaleE1');
        $browser->setValue('pricesAtDate', date('d-m-Y', $valior));
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
    
        $browser->press('Артикул');
        $browser->setValue('productId', '7');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010+03*04');//22
        $browser->setValue('packPrice', '01+3*0.4');//2.2
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
        
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', '5');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '021-03*4');//9
        $browser->setValue('packPrice', '09,20+0,3*04');//10.4
        $browser->setValue('discount', 2);
             
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
             
        if(strpos($browser->gettext(), '27,74')) {
        } else {
            return "Грешно ДДС";
        }
    
        if(strpos($browser->gettext(), 'Сто шестдесет и шест BGN и 0,42')) {
        } else {
            return "Грешна обща сума";
        }
    
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '10');
        $browser->setValue('peroCase', '1');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', 'BG21 CREX 9260 3114 5487 01');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 1);
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Чакащо плащане: Просрочено')) {
        } else {
            return "Грешно чакащо плащане";
        }
    }
    /**
     * 11.
     * Проверка състояние плащане - просрочено, част. доставено, част.платено и фактурирано
     * Нова продажба на съществуваща фирма с папка 3448.13
     */ 
    //http://localhost/unit_MinkPbgERP/CreateSaleExped/
    function act_CreateSaleExped()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
          
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
    
        // нова продажба - проверка има ли бутон
    
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
       
        $valior=strtotime("-4 Days");
        $browser->setValue('valior', date('d-m-Y', $valior)); 
        $browser->setValue('reff', 'exp');
        $browser->setValue('bankAccountId', 1);
        $browser->setValue('note', 'MinkPTestCreateSaleE');
        $browser->setValue('pricesAtDate', date('d-m-Y', $valior));
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        $browser->press('Артикул');
        $browser->setValue('productId', '7');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010+03*08');//34
        $browser->setValue('packPrice', '066-3*0.8');//63,6
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
       
        // Записваме артикула и добавяме нов
        
        $browser->setValue('productId', '5');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '068-03*8');//44
        $browser->setValue('packPrice', '07.20+0.3*08');//9.6
        $browser->setValue('discount', 2);
     
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', '9');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 117);
        $browser->setValue('packPrice', 1.6207);
        $browser->setValue('discount', 1);
          
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', '3');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '140 / 05-03*08');//4
        $browser->setValue('packPrice', '100/05+3*08');//44
        $browser->setValue('discount', 1);
    
        // Записваме артикула
        $browser->press('Запис');
            
        // активираме продажбата
        $browser->press('Активиране');
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), '574,69')) {
        } else {
            return "Грешно ДДС";
        }
    
        if(strpos($browser->gettext(), 'Три хиляди четиристотин четиридесет и осем BGN и 0,13')) {
        } else {
            return "Грешна обща сума";
        }
    
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', '1');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', 'BG21 CREX 9260 3114 5487 01');
        $browser->setValue('amountDeal', '100');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 1);
        $browser->press('Чернова');
        $browser->press('Контиране');
      
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('date', date('d-m-Y', $valior));
        $valior=strtotime("-1 day");
        $browser->setValue('dueDate', date('d-m-Y', $valior));
        //$browser->setValue('numlimit', '2000000 - 3000000');
        $browser->setValue('numlimit', '0 - 2000000');
        $browser->press('Чернова');
         
        $browser->press('Контиране');
        
        // протокол
        $browser->press('Пр. услуги');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Контиране')) {
        //  $browser->press('Контиране');
        //}
        if(strpos($browser->gettext(), 'Чакащо плащане: Просрочено')) {
        } else {
            return "Грешно чакащо плащане";
        }   
    }
    
    /**
     * 11.
     * Проверка състояние плащане - просрочено, Метод - До x дни след фактуриране
     * Нова продажба на съществуваща фирма с папка
     */
    //http://localhost/unit_MinkPbgERP/CreateSaleOverdue3days/
    function act_CreateSaleOverdue3days()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
         
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
        
        $valior=strtotime("-4 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('reff', 'А1234');
        $browser->setValue('bankAccountId', 1);
        $browser->setValue('note', 'MinkPTestCreateSaleOverdue');
        $browser->setValue('pricesAtDate', date('d-m-Y', $valior));
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
    
        $browser->press('Артикул');
        $browser->setValue('productId', '7');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010+03*08');//34
        $browser->setValue('packPrice', '010+3*0.8');//12.4
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
       
        // Записваме артикула и добавяме нов
    
        $browser->setValue('productId', '5');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '03*08-010');//14
        $browser->setValue('packPrice', '01.20+0.3*08');//3.6
        $browser->setValue('discount', 2);
             
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', '9');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 17);
        $browser->setValue('packPrice', '1.017');
        $browser->setValue('discount', 1);
             
        // Записваме артикула
        $browser->press('Запис');
        
        // активираме продажбата
        $browser->press('Активиране');
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
             
        if(strpos($browser->gettext(), '95,09')) {
        } else {
            return "Грешно ДДС";
        }
    
        if(strpos($browser->gettext(), 'Петстотин и седемдесет BGN и 0,55')) {
        } else {
            return "Грешна обща сума";
        }
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 1);
        $browser->press('Чернова');
        $browser->press('Контиране');
             
        // протокол
        $browser->press('Пр. услуги');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Контиране')) {
        //  $browser->press('Контиране');
        //}
    
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('date', date('d-m-Y', $valior));
        $valior=strtotime("-1 Day");
        $browser->setValue('dueDate', date('d-m-Y', $valior));
        //$browser->setValue('numlimit', '2000000 - 3000000');
        $browser->setValue('numlimit', '0 - 2000000');
        $browser->press('Чернова');
       
        $browser->press('Контиране');
    
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', '1');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', 'BG21 CREX 9260 3114 5487 01');
        $browser->setValue('amountDeal', '100');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Чакащо плащане: Просрочено')) {
        } else {
            return "Грешно чакащо плащане";
        }    
    }
    
    /**
    * 10.
    * Проверка състояние плащане - чакащо, метод - до x дни след фактуриране (3,7,10,15,21,30) в деня на падеж
    * Нова продажба на съществуваща фирма с папка
    */
    //http://localhost/unit_MinkPbgERP/CreateSaleWaitP/
    function act_CreateSaleWaitP()
        {
                
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
        
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
                              
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
        
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
             
        $valior=strtotime("-7 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('reff', 'А1234');
        $browser->setValue('bankAccountId', 1);
        $browser->setValue('note', 'MinkPTestCreateSaleWaitP');
        $browser->setValue('pricesAtDate', date('d-m-Y', $valior));
        $browser->setValue('paymentMethodId', "До 7 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
             
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        
        $browser->press('Артикул');
        $browser->setValue('productId', '7');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '089-07*08');//33
        $browser->setValue('packPrice', '07+3*0.8');//9.4
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
        
        // Записваме артикула и добавяме нов
        
        $browser->setValue('productId', '5');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100-03*8');//76
        $browser->setValue('packPrice', '09.20+0.3*08');//11.6
        $browser->setValue('discount', 2);
                 
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', '9');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 107);
        $browser->setValue('packPrice', 1.0127);
        $browser->setValue('discount', 1);
            
        // Записваме артикула
        $browser->press('Запис');
                
        // активираме продажбата
        $browser->press('Активиране');
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
                 
        if(strpos($browser->gettext(), '254,43')) {
        } else {
            return "Грешно ДДС";
        }
        
        if(strpos($browser->gettext(), 'Хиляда петстотин двадесет и шест BGN и 0,57')) {
                     
        } else {
            return "Грешна обща сума";
        }
        
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 1);
        $browser->press('Чернова');
        $browser->press('Контиране');
                  
        // протокол
        $browser->press('Пр. услуги');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Контиране')) {
        //  $browser->press('Контиране');
        //}
        
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('date', date('d-m-Y', $valior));
        $valior=strtotime("now");
        $browser->setValue('dueDate', date('d-m-Y', $valior));
        //$browser->setValue('numlimit', '2000000 - 3000000');
        $browser->setValue('numlimit', '0 - 2000000');
        $browser->press('Чернова');
        
        $browser->press('Контиране');
        
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', '1');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', 'BG21 CREX 9260 3114 5487 01');
        $browser->setValue('amountDeal', '100');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Чакащо плащане: Да')) {
        } else {
            return "Грешно чакащо плащане в деня на падеж";
        }
    }
        
     
    /**
     *9.Рецепта - не приема мярката, като я няма - също не записва
     *
     */
    //http://localhost/unit_MinkPbgERP/CreateBom/
    function act_CreateBom()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
           
        $browser->click('Каталог');
        $browser->click('Продукти');
        $browser->click('Други продукти');
        $browser->click('Рецепти');
        $browser->click('Добавяне на нова търговска технологична рецепта');
        //Return $browser->getHtml();
        //$browser->hasText('Добавяне на търговска рецепта към');
        $browser->setValue('expenses', '13');
        $browser->setValue('quantityForPrice', '100');
        $browser->press('Чернова');
        $browser->press('Влагане');
        $browser->setValue('resourceId', '1');
        $browser->setValue('propQuantity', '19');
        //не приема мярката, като я няма - също не записва
        $browser->setValue('measureId', '3');
        return $browser->getHtml();
        $browser->press('Запис и нов');
        $browser->setValue('resourceId', '1');
        $browser->setValue('propQuantity', '1 + $Начално= 10');
        $browser->press('Запис');
        $browser->press('Активиране');
        $browser->press('OK');
       
    }
         
    /**
    * 8.Създава задание за производство 
    */
    //http://localhost/unit_MinkPbgERP/CreatePlanningJob/
    function act_CreatePlanningJob()
    {
        
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
        
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pdainovska');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
        
        
        }
    
    /**
    * 7.Нова оферта на съществуваща фирма с папка
    */
    ///http://localhost/unit_MinkPbgERP/CreateQuotation/
    function act_CreateQuotation()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
       
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
                
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('N');
        $Company = "NEW INTERNATIONAL GMBH";
        $browser->click($Company);
    
        $browser->press('Папка');
    
        // нова оферта
        $browser->press('Нов...');
        $browser->press('Оферта');
        //$browser->hasText('Създаване на оферта в');
        $browser->press('Чернова');
        
        // Добавяме артикул - нестандартен
        $browser->press('Добавяне');
        $browser->setValue('productId', '13');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 100);
        $browser->setValue('packPrice', 1);
        //$browser->setValue('discount', 1);
                
        // Записваме артикула и добавяме нов
        $browser->press('Запис и Нов');
        $browser->setValue('productId', '12');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 100);
        $browser->setValue('packPrice', 2);
        //$browser->setValue('discount', 2);
        // Записваме артикула 
        $browser->press('Запис');

        // Записваме артикула и добавяме опционален - услуга
        $browser->press('Опционален артикул');
        $browser->setValue('productId', '9');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 1);
        $browser->setValue('packPrice', 100);
        //$browser->setValue('discount', 2);
        // Записваме артикула
        $browser->press('Запис');
        
        // Активираме офертата
        $browser->press('Активиране');
         
    }
    
    /**
     * 6.Запитване от съществуваща фирма с папка
     */
    //http://localhost/unit_MinkPbgERP/CreateInq/
    function act_CreateInq()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
         
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
    
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('N');
        $Company = "NEW INTERNATIONAL GMBH";
        $browser->click($Company);
        $browser->press('Папка');
    
        // ново запитване
        $browser->press('Нов...');
        $browser->press('Запитване');
        //$browser->hasText('Създаване на запитване в');
        $browser->press('Чернова');
        $browser->setValue('inqDescription', 'Торбички');
        $browser->setValue('measureId', '1');
        $browser->setValue('quantity1', '1000');
        $browser->setValue('name', 'Peter Neumann');
        $browser->setValue('country', 'Германия');
        $browser->setValue('email', 'pneumann@gmail.com');
        $browser->press('Чернова');
        $browser->press('Артикул');
        $browser->setValue('name', 'Артикул по запитване');
        $browser->press('Запис');
        
    }
    
    
    /**
     * 5.Търсим фирма, ако я има - отваряме и редактираме, ако не - създаваме нова фирма
     */
    //http://localhost/unit_MinkPbgERP/CreateEditCompany/
    function act_CreateEditCompany()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
        $browser->click('Визитник');
        // търсим фирмата
        $browser->click('N');
        //$browser->hasText( $Company);
    
        $Company = "NEW INTERNATIONAL GMBH";
    
        //if(strpos($browser->gettext(), $Company)  && 0) {  - не работи
        if(strpos($browser->gettext(), $Company)) {
            //bp($browser->gettext());
            //има такава фирма - редакция
            $browser->click($Company);
            $browser->press('Редакция');
    
            //Проверка дали сме в редакция
            //$browser->hasText('Редактиране на запис в "Фирми"');
    
        } else {
             
            // Правим нова фирма
            $browser->press('Нова фирма');
            //Проверка дали сме в добавяне
            //$browser->hasText('Добавяне на запис в "Фирми"');
    
        }
        $browser->setValue('name', $Company);
        $browser->setValue('country', 'Германия');
        $browser->setValue('place', 'Stuttgart');
        $browser->setValue('pCode', '70376');
        $browser->setValue('address', 'Brückenstraße 44А');
        //$browser->setValue('fax', '086711123');
        //$browser->setValue('tel', '086111111');
        $browser->setValue('uicId', '564749');
        $browser->setValue('website', 'http://www.new-international.com');
        $browser->setValue('Клиенти', '1');
        $browser->setValue('info', 'Фирма за тестове');
    
        $browser->press('Запис');
    
        // Създаване на папка на нова фирма/отваряне на папката на стара
        if(strpos($browser->gettext(), $Company)) {
            $browser->press('Папка');
        }
         
    }
    /**
     * 5.Търсим фирма, ако я има - отваряме и редактираме, ако не - създаваме нова фирма. Ако има повече от една страница, не работи добре.  Да се търси по буква!!!
     */
    //if(strpos($browser->gettext(), $Company)  && 0) {  - не намира съществуваща фирма
    //if(strpos($browser->gettext(), $Company)) {намира фирмата, но дава грешка при търсене на несъществуваща,  заради търсенето
    //http://localhost/unit_MinkPbgERP/TestFirm/
    function act_TestFirm()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
         
        //$browser->hasText('Известия');
        //$browser->hasText('Pavlinka');
    
        $browser->click('Визитник');
        // търсим фирмата
        //$browser->click('P');
        $Company = "Пролет ООД";
        //$browser->open("/crm_Companies/?id=&Rejected=&alpha=&Cmd[default]=1&search={$Company}&users=all_users&order=alphabetic&groupId=&Cmd[default]=Филтрирай");
    
        //if(strpos($browser->gettext(), $Company)  && 0) {  - не намира съществуваща фирма
        //if(strpos($browser->gettext(), $Company)) { намира фирмата, но дава грешка при търсене на несъществуваща, заради търсенето
        if(strpos($browser->gettext(), $Company)) {
            //bp($browser->gettext());
            //има такава фирма - редакция
            $browser->click($Company);
            $browser->press('Редакция');
            //Проверка дали сме в редакция
            //$browser->hasText('Редактиране');
            //$browser->hasText('Фирма');
             
        } else {
             
            // Правим нова фирма
             
            $browser->press('Нова фирма');
             
            //$browser->hasText('Добавяне на запис');
            //$browser->hasText('Фирма');
        }
        $browser->setValue('name', $Company);
        $browser->setValue('place', 'Плевен');
        $browser->setValue('pCode', '7800');
        $browser->setValue('address', 'ул.Днепър, №11');
        $browser->setValue('fax', '086898989');
        $browser->setValue('tel', '086799999');
        $browser->setValue('info', 'Тази фирма е редактирана');
        $browser->setValue('Клиенти', '1');
        $browser->press('Запис');
        // Създаване на папка
    
        $browser->press('Папка');
        
        }
         
    /**
     * 5.редакция на фирма OK
     */
    //http://localhost/unit_MinkPbgERP/EditCompany/
    function act_EditCompany()
    {
        // редакция на фирма OK
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
         
        //Отваряме папката на фирма Фирма bgErp
        $browser->click('Визитник');
        $browser->click('F');
        //$browser->hasText('Фирма bgErp');
    
        $browser->click('Фирма bgErp');
    
        //Проверка дали сме в Фирма bgErp
        //$browser->hasText('Фирма bgErp - .....');
         
        $browser->press('Редакция');
        //Проверка дали сме в редакция
    
        //$browser->hasText('Редактиране на запис в "Фирми"');
        $browser->setValue('address', 'ул.Втора, №2');
        $browser->setValue('pCode', '7000');
        $browser->setValue('fax', '333333');
        $browser->setValue('tel', '222222');
        $browser->setValue('uicId', '200021786');
        $browser->press('Запис');
         
        return ' Фирма-запис на редакцията';
         
    }
    
    /**
     * 5.Създаване на нова фирма и папка към нея, допуска дублиране - ОК
     */
    //http://localhost/unit_MinkPbgERP/CreateCompany/
    function act_CreateCompany()
    {
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
        
        // Правим нова фирма
    
        $browser->click('Визитник');
        $browser->press('Нова фирма');
         
        //$browser->hasText('Добавяне на запис в "Фирми"');
        //$browser->hasText('Фирма');
    
        $browser->setValue('name', 'Фирма bgErp');
        $browser->setValue('place', 'Ст. Загора');
        $browser->setValue('pCode', '6400');
        $browser->setValue('address', 'ул.Бояна, №122');
        $browser->setValue('fax', '036111111');
        $browser->setValue('tel', '036111111');
        $browser->setValue('uicId', '110001322');
        $browser->setValue('Клиенти', '1');
        $browser->press('Запис');
    
        if (strpos($browser->getText(),"Предупреждение:")){
            $browser->setValue('Ignore', 1);
            $browser->press('Запис');
        }
    
        // Създаване на папка на нова фирма
    
        $browser->press('Папка');
        //bp($browser->getText());
    
    }
    
    /**
     * 4.Създава нов артикул - продукт през папката - Добавяне рецепти - Дава грешка за мярката на артикулите към рецептата
     */
    //http://localhost/unit_MinkPbgERP/CreateProduct1/
    function act_CreateProduct1()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
        //$browser->hasText('Известия');
        //$browser->hasText('Pavlinka');
    
        // Правим нов артикул - продукт
        $browser->click('Каталог');
        $browser->click('Продукти');
        $browser->press('Артикул');
        $browser->setValue('name', 'Плик 7 л');
        $browser->setValue('code', 'plik7');
        $browser->setValue('measureId', '9');
        $browser->press('Запис');
    
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            $browser->click('Плик 7 л');
            //Return $browser->getHtml();
        }
        //Добавяне рецепта
        $browser->click('Рецепти');
        $browser->click('Добавяне на нова търговска технологична рецепта');
        //Return $browser->getHtml();
        //$browser->hasText('Добавяне на търговска рецепта към');
        $browser->setValue('expenses', '13');
        $browser->setValue('quantityForPrice', '166');
    
        $browser->press('Чернова');
        $browser->press('Влагане');
    
        $browser->setValue('resourceId', '3');
         
        if(strpos($browser->gettext(), 'Други външни услуги')) {
            //return 'Други външни услуги';
        } else {
            return 'артикул';
        }
    
        //$browser->setValue('packagingId', '9');
        $browser->setValue('propQuantity', '19');
    
        if(strpos($browser->gettext(), 'packagingId')) {
            //return 'packagingId' ;
        } else {
            return 'мярка';
        }
        //Return $browser->getHtml();
    
        ///// Дава грешка за мярката!!!
        //Return $browser->getHtml();
        $browser->press('Запис и нов');
        $browser->setValue('resourceId', '1');
        $browser->setValue('propQuantity', '1 + $Начално= 10');
        $browser->press('Запис');
        $browser->press('Активиране');
        $browser->press('OK');
    }
    
    
    /**
     * 4.Създава нов артикул - продукт, ако го има - редакция
     */
    //http://localhost/unit_MinkPbgERP/CreateProduct/
    function act_CreateProduct()
    {
         
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
        // проверка потребител/парола
        //Грешка:Грешна парола или ник!
        //$browser->hasText('Известия');
        //$browser->hasText('Pavlinka');
        // Правим нов артикул - продукт
    
        $browser->click('Каталог');
        $browser->press('Нов запис');
         
        //$browser->hasText('Избор на папка');
        //$browser->hasText('Категория');
    
        $browser->setValue('catcategorieId', '7');
        $browser->press('Напред');
    
        $browser->setValue('name', 'Чувал голям 120 L');
        $browser->setValue('code', 'smet120');
        $browser->setValue('measureId', '9');
        //$browser->setValue('groups[8]', 'On');
        $browser->press('Запис');
    
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
               
            $browser->press('Отказ');
            $browser->click('Продукти');
            $browser->click('Чувал голям 120 L');
           
            $browser->press('Редакция');
            $browser->setValue('info', 'червен');
            $browser->press('Запис');
            $browser->click('Добавяне на нов параметър');
            $browser->setValue('paramId', 'Дължина (см)');
           
            ///// Дава грешка на стойността на параметъра!!!
            $browser->setValue('paramValue', '2');
            Return $browser->getHtml();
            $browser->press('Запис');
        }
        //Добавяне рецепти?
        //$browser->click('Рецепти');
    }
    
    /**
     * 3.Създава нова каса
     */
    ///http://localhost/unit_MinkPbgERP/CreateCase/
    function act_CreateCase()
    {
    
        $browser = cls::get('unit_Browser');
    
        // bgERP
        $url = 'http://localhost/';
        $nick = 'Pavlinka';
        $pass = '111111';
    
        // Reload
        //$url = 'http://reload.bgerp.com/';
        //$nick = 'Ceo1';
        //$pass = '123456';
    
        $browser->start($url);
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', $nick);
        $browser->setValue('pass', $pass);
        $browser->press('Вход');
        // проверка потребител/парола
        //Грешка:Грешна парола или ник!
    
        //$browser->hasText('Известия');
        //$browser->hasText('Pavlinka');
    
        // Правим нова каса
        $browser->click('Каси');
        $browser->press('Нов запис');
         
        //$browser->hasText('Добавяне на запис в "Фирмени каси"');
    
        $browser->setValue('name', 'КАСА 2');
        $browser->setValue('Pavlinka', '1');
        //return  $browser->getHtml();
        $browser->press('Запис');
         
        if (strpos($browser->getText(),'Непопълнено задължително поле')){
            $browser->press('Отказ');
            Return Грешка;
        }
    
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            Return Дублиране;
        }
    
    }
    
    
    /**
     * 2.Създава нова банкова сметка
     */
    //http://localhost/unit_MinkPbgERP/CreateBankAcc/
    function act_CreateBankAcc()
    {
         
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
        // проверка потребител/парола
        //Грешка:Грешна парола или ник!
        //$browser->hasText('Известия');
        //$browser->hasText('Pavlinka');
        // Правим нова банка
        $browser->click('Банки');
        $browser->press('Нов запис');
         
        //$browser->hasText('Добавяне на запис в "Банкови сметки на фирмата"');
    
        $browser->setValue('iban', 'BG21 CREX 9260 3114 5487 01');
        $browser->setValue('currencyId', '1');
        $browser->setValue('Pavlinka', '1');
        //$browser->setValue('Оператори....', 'On');
        $browser->press('Запис');
    
        if (strpos($browser->getText(),'Непопълнено задължително поле')){
            $browser->press('Отказ');
            Return Грешка;
        }
    
        if (strpos($browser->getText(),"Вече има наша сметка с този IBAN")){
            $browser->press('Отказ');
            Return Дублиране;
        }
    
    }
    
    /**
     * 1. Създава нов склад
     */
    //http://localhost/unit_MinkPbgERP/CreateStore/
    function act_CreateStore()
    {
         
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
        //return  $browser->getHtml();
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
         
        $browser->press('Вход');
        // проверка потребител/парола
        //Грешка:Грешна парола или ник!
        //$browser->hasText('Известия');
        //$browser->hasText('Pavlinka');
        // Правим нов склад
        //return  $browser->getHtml();
        $browser->click('Склад');
        $browser->click('Складове');
        $browser->press('Нов запис');
         
        //$browser->hasText('Добавяне на запис в "Складове"');
         
        $browser->setValue('name', 'Склад 3');
        //$ekip='Екип "Главен офис"';
        //$browser->setValue($ekip, '1');
        $browser->setValue('Pavlinka', '1');
         
        $browser->press('Запис');
    
        if (strpos($browser->getText(),'Непопълнено задължително поле')){
            $browser->press('Отказ');
            Return Грешка;
        }
    
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            Return Дублиране;
        }
    
    }
      
    
}
   
