<?php

declare(strict_types=1);

return [
    'name_contains_comma' => '词语不能包含逗号：Skribbl.io 用它作为分隔符。',
    'name_too_long' => '词语不能超过 :max 个字符。',
    'name_taken' => '该词语已在词组中。',
    'name_required' => '请输入一个词语。',
    'name_repeated' => '你多次输入了这个词语。',
    'no_longer_in_group' => '该词语已不在词组中。',
    'add' => [
        'required' => '请至少输入一个词语。',
        'max' => '一次最多只能添加 :max 个词语。',
    ],
    'update' => [
        'required' => '请至少修改一个词语。',
        'max' => '一次最多只能修改 :max 个词语。',
    ],
    'delete' => [
        'required' => '请至少选择一个词语。',
        'max' => '一次最多只能删除 :max 个词语。',
        'no_longer_in_group' => '所选词语中有一个已不在词组中。',
    ],
];
