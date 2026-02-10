<?php

namespace App\Constants;

class ExportField{
    
    public $deposits = [
        'user'=>[
            'heading'=>'Username',
            'relation'=>[
                'relation_name'=>'user',
                'column'=>'username',
            ]
        ],
        'trx'=>[
            'heading'=>'Trx Number'
        ],
        'amount'=>[
            'heading'=>'Amount',
            'showAmount'=>true,
        ],
        'charge'=>[
            'heading'=>'Charge',
            'showAmount'=>true,
        ],
        'payment_charge'=>[
            'heading'=>'Payment Charge',
            'showAmount'=>true,
        ],
        'final_amo'=>[
            'heading'=>'After Charge',
            'showAmount'=>true,
        ],
        'gateway'=>[
            'heading'=>'Gateway Name',
            'relation'=>[
                'relation_name'=>'gateway',
                'column'=>'name',
            ]
        ],
        'method_currency'=>[
            'heading'=>'Method Currency',
        ],
        'created_at'=>[
            'heading'=>'Date',
            'showDateTime'=>true
        ],
    ];

    public $withdrawals = [
        'user'=>[
            'heading'=>'Username',
            'relation'=>[
                'relation_name'=>'user',
                'column'=>'username',
            ]
        ],
        'trx'=>[
            'heading'=>'Trx Number'
        ],
        'amount'=>[
            'heading'=>'Amount',
            'showAmount'=>true,
        ],
        'charge'=>[
            'heading'=>'Charge',
            'showAmount'=>true,
        ],
        'final_amount'=>[
            'heading'=>'After Charge',
            'showAmount'=>true,
        ],
        'gateway'=>[
            'heading'=>'Gateway Name',
            'relation'=>[
                'relation_name'=>'method',
                'column'=>'name',
            ]
        ],
        'currency'=>[
            'heading'=>'Method Currency',
        ],
        'created_at'=>[
            'heading'=>'Date',
            'showDateTime'=>true
        ],
    ];

    public $transactions = [
        'user'=>[
            'heading'=>'Username',
            'relation'=>[
                'relation_name'=>'user',
                'column'=>'username',
            ]
        ],
        'trx'=>[
            'heading'=>'Trx Number'
        ],
        'trx_type'=>[
            'heading'=>'Trx Type'
        ], 
        'amount'=>[
            'heading'=>'Amount',
            'showAmount'=>true,
        ],
        'charge'=>[
            'heading'=>'Charge',
            'showAmount'=>true,
        ],
        'post_balance'=>[
            'heading'=>'Post Charge',
            'showAmount'=>true,
        ],
        'details'=>[
            'heading'=>'Details',
        ],
        'remark'=>[
            'heading'=>'Remark',
        ],
        'created_at'=>[
            'heading'=>'Date',
            'showDateTime'=>true
        ],
    ];
    
}

