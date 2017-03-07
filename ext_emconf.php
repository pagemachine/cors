<?php

$EM_CONF[$_EXTKEY] = [
  'title' => 'CORS',
  'description' => 'Cross Origin Resource Sharing for TYPO3',
  'category' => 'fe',
  'author' => 'Mathias Brodala',
  'author_email' => 'mbrodala@pagemachine.de',
  'author_company' => 'PAGEmachine AG',
  'state' => 'stable',
  'version' => '2.0.3',
  'constraints' => [
    'depends' => [
      'typo3' => '6.2.0-8.99.99',
    ],
  ],
];
