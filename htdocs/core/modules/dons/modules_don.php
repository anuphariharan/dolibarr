<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/core/modules/dons/modules_don.php
 *		\ingroup    don
 *		\brief      File of class to manage donation document generation
 */
require_once(DOL_DOCUMENT_ROOT."/core/class/commondocgenerator.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/dons/class/don.class.php");



/**
 *	    \class      ModeleDon
 *		\brief      Classe mere des modeles de dons
 */
abstract class ModeleDon extends CommonDocGenerator
{
    var $error='';

    /**
     *      \brief      Return list of active generation modules
     * 		\param		$db		Database handler
     */
    function liste_modeles($db)
    {
        global $conf;

        $type='donation';
        $liste=array();

        include_once(DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php');
        $liste=getListOfModels($db,$type,'');

        return $liste;
    }
}


/**
 *	\class 		ModeleNumRefDons
 *	\brief  	Classe mere des modeles de numerotation des references des dons
 */
abstract class ModeleNumRefDons
{
    var $error='';

    /**     \brief     	Return if a module can be used or not
     *      	\return		boolean     true if module can be used
     */
    function isEnabled()
    {
        return true;
    }

    /**     \brief      Renvoi la description par defaut du modele de num�rotation
     *      \return     string      Texte descripif
     */
    function info()
    {
        global $langs;
        $langs->load("bills");
        return $langs->trans("NoDescription");
    }

    /**     \brief      Renvoi un exemple de numerotation
     *      \return     string      Example
     */
    function getExample()
    {
        global $langs;
        $langs->load("bills");
        return $langs->trans("NoExample");
    }

    /**     \brief      Test si les numeros deja en vigueur dans la base ne provoquent pas d
     *                  de conflits qui empechera cette numerotation de fonctionner.
     *      \return     boolean     false si conflit, true si ok
     */
    function canBeActivated()
    {
        return true;
    }

    /**     \brief      Renvoi prochaine valeur attribuee
     *      \return     string      Valeur
     */
    function getNextValue()
    {
        global $langs;
        return $langs->trans("NotAvailable");
    }

    /**     \brief      Renvoi version du module numerotation
     *      	\return     string      Valeur
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') return $langs->trans("VersionDevelopment");
        if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
        if ($this->version == 'dolibarr') return DOL_VERSION;
        return $langs->trans("NotAvailable");
    }
}


/**
 *	\brief      Cree un don sur disque en fonction du modele de DON_ADDON_PDF
 *	\param	    db  			objet base de donnee
 *	\param	    id				id du don e creer
 *	\param	    message			message
 *	\param	    modele			force le modele a utiliser ('' par defaut)
 *	\param		outputlangs		objet lang a utiliser pour traduction
 *	\return     int         	0 si KO, 1 si OK
 */
function don_create($db, $id, $message, $modele, $outputlangs)
{
    global $conf, $langs;
    $langs->load("bills");

    $dir = DOL_DOCUMENT_ROOT . "/core/modules/dons/";

    // Positionne modele sur le nom du modele � utiliser
    if (! dol_strlen($modele))
    {
        if ($conf->global->DON_ADDON_MODEL)
        {
            $modele = $conf->global->DON_ADDON_MODEL;
        }
        else
        {
            print $langs->trans("Error")." ".$langs->trans("Error_DON_ADDON_MODEL_NotDefined");
            return 0;
        }
    }

    // Charge le modele
    $file = $modele.".modules.php";
    if (file_exists($dir.$file))
    {
        $classname = $modele;

        require_once($dir.$file);

        $obj = new $classname($db);

        $obj->message = $message;

        // We save charset_output to restore it because write_file can change it if needed for
        // output format that does not support UTF8.
        $sav_charset_output=$outputlangs->charset_output;
        if ($obj->write_file($id,$outputlangs) > 0)
        {
            $outputlangs->charset_output=$sav_charset_output;

			// we delete preview files
        	require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
            dol_delete_preview($obj);
            return 1;
        }
        else
        {
            $outputlangs->charset_output=$sav_charset_output;
            dol_syslog("Erreur dans don_create");
            dol_print_error($db,$obj->error);
            return 0;
        }
    }
    else
    {
        print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$dir.$file);
        return 0;
    }
}

?>
