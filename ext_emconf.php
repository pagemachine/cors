<?php

$EM_CONF[$_EXTKEY] = array(
  'title' => 'CORS',
  'description' => 'Cross Origin Resource Sharing for TYPO3 CMS.',
  'category' => 'fe',
  'author' => 'Mathias Brodala',
  'author_email' => 'mbrodala@pagemachine.de',
  'author_company' => 'PAGEmachine AG',
  'state' => 'stable',
  'version' => '1.2.10',
  'constraints' => array(
    'depends' => array(
      'cms' => '',
      'typo3' => '6.2.0-6.2.99',
    ),
    'conflicts' => array(
    ),
    'suggests' => array(
    ),
  ),
);
