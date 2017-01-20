<?php

/**
 *  Клас  'unit_MinkPListProduct' - PHP тестове за листване на продукти към контрагент и продажба по списък
 *
 * @category  bgerp
 * @package   tests
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class unit_MinkPListProduct extends core_Manager {
    //Изпълнява се след unit_MinkPbgERP!
    //http://localhost/unit_MinkPListProduct/Run/
    public function act_Run()
    {
        if (!TEST_MODE) {
            return;
        }
        
        $res = '';
        $res .= '<br>'.'MinkPListProduct';
        $res .=  " 1.".$this->act_CreateCatListings();
        $res .=  " 2.".$this->act_SetCustomerConditions();
        $res .= "  3.".$this->act_CreateSaleList();
        $res .= "  4.".$this->act_CreatePurchaseList();
        
        return $res;
    }
       
    /**
     * Логване
     */
    public function SetUp()
    {
        $browser = cls::get('unit_Browser');
        $host = unit_Setup::get('DEFAULT_HOST');
        //$browser->start('http://localhost/');
        $browser->start($host);
        //Потребител DEFAULT_USER (bgerp)
        $browser->click('Вход');
        $browser->setValue('nick', unit_Setup::get('DEFAULT_USER'));
        $browser->setValue('pass', unit_Setup::get('DEFAULT_USER_PASS'));
        $browser->press('Вход');
        return $browser;
    }
    
    /**
     * Избор на фирма
     */
    public function SetFirm()
    {
        $browser = $this->SetUp();
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        //$browser->press('Папка');
        return $browser;
    }
    
    /**
     * Избор на чуждестранна фирма
     */
    public function SetFirmEUR()
    {
        $browser = $this->SetUp();
        $browser->click('Визитник');
        $browser->click('N');
        $Company = 'NEW INTERNATIONAL GMBH';
        $browser->click($Company);
        //$browser->press('Папка');
        return $browser;
    }
    
    /**
     * Добавяне на търговски условия за листване - покупка и продажба в проект
     */
     
    //http://localhost/unit_MinkPListProduct/CreateCatListings/
    function act_CreateCatListings()
    {
    
        // Логване
        $browser = $this->SetUp();
        
        $browser->click('Всички');
        $browser->press('Нов проект');
        $browser->setValue('name', 'Търговски условия за листване');
        $browser->setValue('Листвани артикули', True); 
        $browser->press('Запис');
        $browser->press('Папка');
        //Списък за листване при покупки 
        $browser->press('Листване на артикули');
        $browser->setValue('title', 'За покупка');
        $browser->setValue('type', 'Купуваеми');
        $browser->press('Чернова');
        
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Артикул ДДС 9');
        $browser->refresh('Запис');
        $browser->setValue('reff', 'D9');
        $browser->setValue('moq', '20');
        $browser->setValue('multiplicity', '10');
       
        // Записване артикула и добавяне нов
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('reff', 's50');
        $browser->setValue('moq', '1000');
        $browser->setValue('multiplicity', '100');
        // Записване на артикула
        $browser->press('Запис');
        $browser->press('Активиране');
        
        //Списък за листване при продажби
        $browser->click('Търговски условия за листване');
        $browser->press('Листване на артикули');
        $browser->setValue('title', 'За продажба');
        $browser->setValue('type', 'Продаваеми');
        $browser->press('Чернова');
        
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Плик 7 л');
        $browser->refresh('Запис');
        $browser->setValue('reff', 'p7');
        $browser->setValue('moq', '24');
        $browser->setValue('multiplicity', '12');
        // Записване артикула и добавяне нов
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('reff', 's50');
        $browser->setValue('moq', '100');
        $browser->setValue('multiplicity', '50');
        // Записване на артикула
        $browser->press('Запис');
        $browser->press('Активиране');
    }
    
    /**
     * Добавяне на търговски условия за листване към контрагент (продажби и покупки)
     * Не работи!
     */
    //http://localhost/unit_MinkPListProduct/SetCustomerConditions/
    function act_SetCustomerConditions()
    {
    
        // Логване
        $browser = $this->SetUp();
        //Отваряне корицата на фирмата
        $browser = $this->SetFirm();
         
        $browser->click('Търговия');
        $browser->click('Добавяне на ново търговско условие');
        $browser->setValue('conditionId', 'Листвани продукти');
        //return $browser->getHtml();
        $browser->setValue('value', 'За покупка');
        $browser->press('Запис и Нов');
        $browser->setValue('conditionId', 'Листвани продукти');
        $browser->setValue('value', 'За продажба');
        $browser->press('Запис');
          
    }
    
    /**
     * Продажба - артикули по списък
     */
     
    //http://localhost/unit_MinkPListProduct/CreateSaleList/
    function act_CreateSaleList()
    {
    
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне папката на фирмата
         $browser = $this->SetFirm();
      
         $browser->press('Папка');
         
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
        $browser->setValue('reff', 'MinkPListProducts');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPlistVatInclude');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Включено ДДС в цените");
        // Записване черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяне на артикули
        $browser->press('Списък');
        /// Количества на двата артикула
      
        $browser->setValue('quantity1', '100');
        //return $browser->getHtml();
        $browser->setValue('quantity2', '50');
        
        // Записване артикулите
        $browser->press('Импорт');
        //return $browser->getHtml();
        // активиране на продажбата
        $browser->press('Активиране');
       
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Шестдесет и един BGN и 0,73')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
    
        //Проверка на статистиката
        if(strpos($browser->gettext(), '61,73 61,73 0,00 0,00')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешни суми в мастера', 'warning');
        }
        
    }
    
    /**
     * Покупка - артикули по списък
     */
     
    //http://localhost/unit_MinkPListProduct/CreatePurchaseList/
    function act_CreatePurchaseList()
    {
    
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне папката на фирмата
        $browser = $this->SetFirm();
    
        $browser->press('Папка');
         
        // нова покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
        $browser->setValue('reff', 'MinkPListProducts');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPlistVatInclude');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Включено ДДС в цените");
        // Записване черновата на покупката
        $browser->press('Чернова');
    
        // Добавяне на артикули
        $browser->press('Списък');
        /// Количества на двата артикула
    
        $browser->setValue('quantity1', '100');
        //return $browser->getHtml();
        $browser->setValue('quantity2', '50');
    
        // Записване артикулите
        $browser->press('Импорт');
        //return $browser->getHtml();
        // активиране на покупката
        $browser->press('Активиране');
         
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Шестдесет и един BGN и 0,73')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
    
        //Проверка на статистиката
        if(strpos($browser->gettext(), '61,73 61,73 0,00 0,00')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешни суми в мастера', 'warning');
        }
    
    }
    
    
    /**
     * Добавяне на търговски условия за листване - покупка и продажба в папка на клиент
     */
    //http://localhost/unit_MinkPListProduct/ImportListProducts/
    function act_ImportListProducts()
    {
    
        // Логване
        $browser = $this->SetUp();
        //Отваряне корицата на фирмата
        $browser = $this->SetFirmEUR();
        $browser->press('Папка');
        $browser->press('Нов...');
        $browser->press('Листване на артикули');
        $browser->setValue('title', 'За продажба NEW INTERNATIONAL');
        $browser->setValue('type', 'Продаваеми');
        $browser->press('Чернова');
       
         // Добавяне на артикул
        $browser->press('Импорт');
     
        $browser->setValue('from', 'group');
        //$browser->setValue('from', 'sales');
        //return $browser->getHtml();
        //$browser->setValue('Ценова група » Промоция', '15');
        $browser->setValue('group', 'Ценова група » Промоция');
        //$browser->setValue('group', '15');
        // Записване на списъка
        $browser->press('Импорт');
    
    }
    
    /**
     * Продажба - артикули по списък от предишни продажби/група
     */
}