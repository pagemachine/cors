<?php

$EM_CONF['cors'] = [
  'title' => 'CORS',
  'description' => 'Cross Origin Resource Sharing for TYPO3',
  'category' => 'fe',
  'author' => 'Mathias Brodala',
  'author_email' => 'mbrodala@pagemachine.de',
  'author_company' => 'PAGEmachine AG',
  'state' => 'stable',
  'version' => '2.0.2',
  'constraints' => [
    'depends' => [
      'typo3' => '6.2.0-7.6.99',
    ],
    'conflicts' => [
    ],
    'suggests' => [
    ],
  ],
];
