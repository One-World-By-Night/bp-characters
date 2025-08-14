<?php

/** File: includes/helper/static-data.php
 * Text Domain: bp-characters
 * version 2.0.0
 * @author greghacke
 * Function: static data functionality for the plugin
 */

defined('ABSPATH') || exit;

/**
 * Get creature types for dropdown
 * @return array
 */
function bpc_get_creature_types()
{
    return [
        // Vampire - Camarilla
        'vampire-camarilla-assamite' => 'Vampire / Camarilla / Assamite',
        'vampire-camarilla-brujah' => 'Vampire / Camarilla / Brujah',
        'vampire-camarilla-gangrel' => 'Vampire / Camarilla / Gangrel',
        'vampire-camarilla-lasombra' => 'Vampire / Camarilla / Lasombra',
        'vampire-camarilla-malkavian' => 'Vampire / Camarilla / Malkavian',
        'vampire-camarilla-nosferatu' => 'Vampire / Camarilla / Nosferatu',
        'vampire-camarilla-toreador' => 'Vampire / Camarilla / Toreador',
        'vampire-camarilla-tremere' => 'Vampire / Camarilla / Tremere',
        'vampire-camarilla-ventrue' => 'Vampire / Camarilla / Ventrue',
        'vampire-camarilla-other' => 'Vampire / Camarilla / Other',

        // Vampire - Sabbat
        'vampire-sabbat-lasombra' => 'Vampire / Sabbat / Lasombra',
        'vampire-sabbat-tzimisce' => 'Vampire / Sabbat / Tzimisce',
        'vampire-sabbat-assamite-antitribu' => 'Vampire / Sabbat / Assamite Antitribu',
        'vampire-sabbat-brujah-antitribu' => 'Vampire / Sabbat / Brujah Antitribu',
        'vampire-sabbat-gangrel-antitribu' => 'Vampire / Sabbat / Gangrel Antitribu',
        'vampire-sabbat-harbinger-of-skulls' => 'Vampire / Sabbat / Harbinger of Skulls',
        'vampire-sabbat-malkavian-antitribu' => 'Vampire / Sabbat / Malkavian Antitribu',
        'vampire-sabbat-nosferatu-antitribu' => 'Vampire / Sabbat / Nosferatu Antitribu',
        'vampire-sabbat-serpent-of-the-light' => 'Vampire / Sabbat / Serpent of the Light',
        'vampire-sabbat-toreador-antitribu' => 'Vampire / Sabbat / Toreador Antitribu',
        'vampire-sabbat-tremere-antitribu' => 'Vampire / Sabbat / Tremere Antitribu',
        'vampire-sabbat-ventrue-antitribu' => 'Vampire / Sabbat / Ventrue Antitribu',
        'vampire-sabbat-pander' => 'Vampire / Sabbat / Pander',
        'vampire-sabbat-other' => 'Vampire / Sabbat / Other',

        // Vampire - Independent
        'vampire-independent-giovanni' => 'Vampire / Independent / Giovanni',
        'vampire-independent-ravnos' => 'Vampire / Independent / Ravnos',
        'vampire-independent-setite' => 'Vampire / Independent / Followers of Set',
        'vampire-independent-assamite' => 'Vampire / Independent / Assamite',
        'vampire-independent-salubri' => 'Vampire / Independent / Salubri',
        'vampire-independent-samedi' => 'Vampire / Independent / Samedi',
        'vampire-independent-daughters-of-cacophony' => 'Vampire / Independent / Daughters of Cacophony',
        'vampire-independent-other' => 'Vampire / Independent / Other',

        // Vampire - Anarch
        'vampire-anarch-assamite' => 'Vampire / Anarch / Assamite',
        'vampire-anarch-brujah' => 'Vampire / Anarch / Brujah',
        'vampire-anarch-gangrel' => 'Vampire / Anarch / Gangrel',
        'vampire-anarch-malkavian' => 'Vampire / Anarch / Malkavian',
        'vampire-anarch-nosferatu' => 'Vampire / Anarch / Nosferatu',
        'vampire-anarch-toreador' => 'Vampire / Anarch / Toreador',
        'vampire-anarch-tremere' => 'Vampire / Anarch / Tremere',
        'vampire-anarch-ventrue' => 'Vampire / Anarch / Ventrue',
        'vampire-anarch-caitiff' => 'Vampire / Anarch / Caitiff',
        'vampire-anarch-other' => 'Vampire / Anarch / Other',

        // Fera - Garou
        'fera-garou-black-fury' => 'Fera / Garou / Black Fury',
        'fera-garou-bone-gnawer' => 'Fera / Garou / Bone Gnawer',
        'fera-garou-children-of-gaia' => 'Fera / Garou / Children of Gaia',
        'fera-garou-fianna' => 'Fera / Garou / Fianna',
        'fera-garou-get-of-fenris' => 'Fera / Garou / Get of Fenris',
        'fera-garou-glass-walker' => 'Fera / Garou / Glass Walker',
        'fera-garou-red-talon' => 'Fera / Garou / Red Talon',
        'fera-garou-shadow-lord' => 'Fera / Garou / Shadow Lord',
        'fera-garou-silent-strider' => 'Fera / Garou / Silent Strider',
        'fera-garou-silver-fang' => 'Fera / Garou / Silver Fang',
        'fera-garou-stargazer' => 'Fera / Garou / Stargazer',
        'fera-garou-uktena' => 'Fera / Garou / Uktena',
        'fera-garou-wendigo' => 'Fera / Garou / Wendigo',
        'fera-garou-croatoan' => 'Fera / Garou / Croatoan',
        'fera-garou-white-howler' => 'Fera / Garou / White Howler',
        'fera-garou-other' => 'Fera / Garou / Other',

        // Fera - Other
        'fera-bastet' => 'Fera / Bastet',
        'fera-corax' => 'Fera / Corax',
        'fera-gurahl' => 'Fera / Gurahl',
        'fera-kitsune' => 'Fera / Kitsune',
        'fera-mokole' => 'Fera / Mokole',
        'fera-nuwisha' => 'Fera / Nuwisha',
        'fera-ratkin' => 'Fera / Ratkin',
        'fera-rokea' => 'Fera / Rokea',
        'fera-ananasi' => 'Fera / Ananasi',
        'fera-other' => 'Fera / Other',

        // Mage - Traditions
        'mage-traditions-akashic-brotherhood' => 'Mage / Traditions / Akashic Brotherhood',
        'mage-traditions-celestial-chorus' => 'Mage / Traditions / Celestial Chorus',
        'mage-traditions-cult-of-ecstasy' => 'Mage / Traditions / Cult of Ecstasy',
        'mage-traditions-dreamspeakers' => 'Mage / Traditions / Dreamspeakers',
        'mage-traditions-euthanatos' => 'Mage / Traditions / Euthanatos',
        'mage-traditions-order-of-hermes' => 'Mage / Traditions / Order of Hermes',
        'mage-traditions-sons-of-ether' => 'Mage / Traditions / Sons of Ether',
        'mage-traditions-verbena' => 'Mage / Traditions / Verbena',
        'mage-traditions-virtual-adepts' => 'Mage / Traditions / Virtual Adepts',
        'mage-traditions-hollow-ones' => 'Mage / Traditions / Hollow Ones',
        'mage-traditions-other' => 'Mage / Traditions / Other',

        // Mage - Technocracy
        'mage-technocracy-iteration-x' => 'Mage / Technocracy / Iteration X',
        'mage-technocracy-new-world-order' => 'Mage / Technocracy / New World Order',
        'mage-technocracy-progenitors' => 'Mage / Technocracy / Progenitors',
        'mage-technocracy-syndicate' => 'Mage / Technocracy / Syndicate',
        'mage-technocracy-void-engineers' => 'Mage / Technocracy / Void Engineers',
        'mage-technocracy-other' => 'Mage / Technocracy / Other',

        // Changeling - Seelie Court
        'changeling-seelie-boggan' => 'Changeling / Seelie / Boggan',
        'changeling-seelie-eshu' => 'Changeling / Seelie / Eshu',
        'changeling-seelie-nocker' => 'Changeling / Seelie / Nocker',
        'changeling-seelie-pooka' => 'Changeling / Seelie / Pooka',
        'changeling-seelie-redcap' => 'Changeling / Seelie / Redcap',
        'changeling-seelie-satyr' => 'Changeling / Seelie / Satyr',
        'changeling-seelie-sidhe' => 'Changeling / Seelie / Sidhe',
        'changeling-seelie-sluagh' => 'Changeling / Seelie / Sluagh',
        'changeling-seelie-troll' => 'Changeling / Seelie / Troll',

        // Changeling - Unseelie Court
        'changeling-unseelie-boggan' => 'Changeling / Unseelie / Boggan',
        'changeling-unseelie-eshu' => 'Changeling / Unseelie / Eshu',
        'changeling-unseelie-nocker' => 'Changeling / Unseelie / Nocker',
        'changeling-unseelie-pooka' => 'Changeling / Unseelie / Pooka',
        'changeling-unseelie-redcap' => 'Changeling / Unseelie / Redcap',
        'changeling-unseelie-satyr' => 'Changeling / Unseelie / Satyr',
        'changeling-unseelie-sidhe' => 'Changeling / Unseelie / Sidhe',
        'changeling-unseelie-sluagh' => 'Changeling / Unseelie / Sluagh',
        'changeling-unseelie-troll' => 'Changeling / Unseelie / Troll',

        // Changeling - Shadow Court
        'changeling-shadow-court' => 'Changeling / Shadow Court',

        // Changeling - Thallain
        'changeling-thallain' => 'Changeling / Thallain',

        // Changeling - Nunnehi (Native American Fae)
        'changeling-nunnehi' => 'Changeling / Nunnehi',

        // Changeling - Menehune (Hawaiian Fae)
        'changeling-menehune' => 'Changeling / Menehune',

        // Changeling - Hsien (Asian Fae)
        'changeling-hsien' => 'Changeling / Hsien',

        // Changeling - Inanimae (Elemental Fae)
        'changeling-inanimae' => 'Changeling / Inanimae',

        // Wraith - Hierarchy
        'wraith-hierarchy-silent-legion' => 'Wraith / Hierarchy / Silent Legion',
        'wraith-hierarchy-grim-legion' => 'Wraith / Hierarchy / Grim Legion',
        'wraith-hierarchy-emerald-legion' => 'Wraith / Hierarchy / Emerald Legion',
        'wraith-hierarchy-iron-legion' => 'Wraith / Hierarchy / Iron Legion',
        'wraith-hierarchy-skeletal-legion' => 'Wraith / Hierarchy / Skeletal Legion',
        'wraith-hierarchy-penitent-legion' => 'Wraith / Hierarchy / Penitent Legion',
        'wraith-hierarchy-paupers-legion' => 'Wraith / Hierarchy / Paupers Legion',

        // Wraith - Guilds
        'wraith-guild-artificers' => 'Wraith / Guild / Artificers',
        'wraith-guild-harbingers' => 'Wraith / Guild / Harbingers',
        'wraith-guild-haunters' => 'Wraith / Guild / Haunters',
        'wraith-guild-masquers' => 'Wraith / Guild / Masquers',
        'wraith-guild-monitors' => 'Wraith / Guild / Monitors',
        'wraith-guild-oracles' => 'Wraith / Guild / Oracles',
        'wraith-guild-pardoners' => 'Wraith / Guild / Pardoners',
        'wraith-guild-proctors' => 'Wraith / Guild / Proctors',
        'wraith-guild-puppeteers' => 'Wraith / Guild / Puppeteers',
        'wraith-guild-sandmen' => 'Wraith / Guild / Sandmen',
        'wraith-guild-solicitors' => 'Wraith / Guild / Solicitors',
        'wraith-guild-spooks' => 'Wraith / Guild / Spooks',
        'wraith-guild-usurers' => 'Wraith / Guild / Usurers',

        // Wraith - Other Factions
        'wraith-renegade' => 'Wraith / Renegade',
        'wraith-heretic' => 'Wraith / Heretic',
        'wraith-spectre' => 'Wraith / Spectre',
        'wraith-ferryman' => 'Wraith / Ferryman',

        // Hunter - Mercy Creeds
        'hunter-mercy-innocent' => 'Hunter / Mercy / Innocent',
        'hunter-mercy-martyr' => 'Hunter / Mercy / Martyr',
        'hunter-mercy-redeemer' => 'Hunter / Mercy / Redeemer',

        // Hunter - Vision Creeds
        'hunter-vision-visionary' => 'Hunter / Vision / Visionary',
        'hunter-vision-hermit' => 'Hunter / Vision / Hermit',
        'hunter-vision-wayward' => 'Hunter / Vision / Wayward',

        // Hunter - Zeal Creeds
        'hunter-zeal-avenger' => 'Hunter / Zeal / Avenger',
        'hunter-zeal-judge' => 'Hunter / Zeal / Judge',
        'hunter-zeal-defender' => 'Hunter / Zeal / Defender',

        // Hunter - Lost Creeds (rare)
        'hunter-lost-deviant' => 'Hunter / Lost / Deviant',
        'hunter-lost-solitary' => 'Hunter / Lost / Solitary',

        // Hunter - Other
        'hunter-independent' => 'Hunter / Independent',
        'hunter-society-of-leopold' => 'Hunter / Society of Leopold',
        'hunter-arcanum' => 'Hunter / Arcanum',
        'hunter-government' => 'Hunter / Government',

        // Mortals & Kinfolk
        'mortal-ghoul' => 'Mortal / Ghoul',
        'mortal-kinfolk' => 'Mortal / Kinfolk',
        'mortal-kinain' => 'Mortal / Kinain',
        'mortal-sorcerer' => 'Mortal / Sorcerer',
        'mortal-psychic' => 'Mortal / Psychic',
        'mortal-ordinary' => 'Mortal / Other',
    ];
}
