<?php

declare(strict_types=1);

return [
    'already_decided' => '该请求已处理。',

    'not_shareable' => [
        'word_group' => '请先开放该词组的贡献，再接受此请求。',
        'group_collection' => '请先开放该合集的贡献，再接受此请求。',
    ],

    /*
     * Names the requested item after "access" or "contribute".
     */
    'target' => [
        'word_group' => '词组“:name”',
        'group_collection' => '合集“:name”',
        'hidden_word_group' => '一个私有词组',
    ],

    'mail' => [
        'greeting' => ':name，你好：',
        'open' => '打开',
        'requested' => [
            'access' => ':requester 请求访问:target',
            'contribution' => ':requester 请求为:target做贡献',
            'action' => '查看请求',
            'outro' => '你可以在此页面或控制面板中接受或拒绝该请求。',
        ],
        'access_accepted' => [
            'subject' => '已获准访问:target',
            'line' => ':creator 已允许你访问:target。',
        ],
        'access_refused' => [
            'subject' => '访问请求被拒绝',
            'line' => ':creator 拒绝了你访问:target的请求。',
        ],
        'contribution_accepted' => [
            'subject' => '你可以为:target做贡献了',
            'line' => ':creator 已允许你为:target做贡献。',
        ],
        'contribution_refused' => [
            'subject' => '贡献请求被拒绝',
            'line' => ':creator 拒绝了你为:target做贡献的请求。',
        ],
        'share_type' => [
            'full' => [
                'word_group' => '完全共享：在词组开放贡献期间，你可以编辑和删除其中的所有词语，包括他人添加的。',
                'group_collection' => '完全共享：在合集开放贡献期间，你可以移除其中的所有词组，包括他人添加的。',
            ],
            'partial' => [
                'word_group' => '部分共享：你只能编辑和删除自己添加的词语。',
                'group_collection' => '部分共享：你只能移除自己添加的词组。',
            ],
        ],
        'share_type_changed' => [
            'subject' => [
                'word_group' => '你在词组“:name”中的共享方式已更改',
                'group_collection' => '你在合集“:name”中的共享方式已更改',
            ],
            'line' => [
                'word_group' => ':creator 更改了你在词组“:name”中的共享方式。',
                'group_collection' => ':creator 更改了你在合集“:name”中的共享方式。',
            ],
        ],
    ],
];
