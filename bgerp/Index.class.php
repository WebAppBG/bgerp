<?php



/**
 * Клас 'bgerp_Index' -
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_Index extends core_Manager
{
    
    
    /**
     * Дефолт екшън след логване
     */
    function act_Default()
    {   
        if(!cms_Content::fetch('1=1')) {
            if(Mode::is('screenMode', 'narrow')) {
                
                return new Redirect(array('bgerp_Menu', 'Show'));
            } else {
                if(haveRole('powerUser')){
                	
                	return new Redirect(array('bgerp_Portal', 'Show'));
                } else {
                	
                	return new Redirect(array('colab_Profiles', 'Single'));
                }
            }
        } else {
            return new Redirect(array('cms_Content', 'Show'));
        }
    }
}