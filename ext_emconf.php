<?php

$EM_CONF[$_EXTKEY] = [
  'title' => 'CORS',
  'description' => 'Cross Origin Resource Sharing for TYPO3',
  'category' => 'fe',
  'author' => 'Mathias Brodala',
  'author_email' => 'mbrodala@pagemachine.de',
  'author_company' => 'PAGEmachine AG',
  'state' => 'stable',
  'version' => '2.0.5',
  'constraints' => [
    'depends' => [
      'php' => '5.6.0-7.0.99',
      'typo3' => '6.2.0-8.7.99',
    ],
  ],
];
