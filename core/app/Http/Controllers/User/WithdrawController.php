<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Lib\HolidayCalculator;
use App\Models\Withdrawal;
use App\Models\WithdrawMethod;
use App\Models\WithdrawSetting;
use Illuminate\Http\Request;

class WithdrawController extends Controller
{
    public function withdraws(Request $request)
    {   
        $pageTitle = 'Withdraws';

        $user = auth()->user();
        $withdrawMethod = WithdrawMethod::where('status',Status::ENABLE)->get();

        $scopes = ['', 'pending', 'approved', 'rejected'];
        $scope = $request->status;
        
        if(!in_array($scope, $scopes)){
            $notify[] = ['error', 'Unauthorized action'];
            return to_route('user.withdraw.history')->withNotify($notify);
        }

        $user = auth()->user();
        $gateways = Withdrawal::where('user_id', $user->id)->where('status', '!=', Status::PAYMENT_INITIATE)->distinct()->with(['method'=>function($method){
            $method->select('id', 'name');
        }])->get('method_id');

        $withdraws = Withdrawal::where('user_id', $user->id)->where('status', '!=', Status::PAYMENT_INITIATE)->when($scope, function($query) use ($scope){
                $query->$scope();
            })->searchable(['trx'])->filter(['method:method_id'])->dateFilter()
        ->with('method')->orderBy('id','desc');
            
        if($request->export_type){
            return $withdraws->export();
        }
        $withdraws = $withdraws->paginate(getPaginate());

        return view('Template::user.withdraw.withdraws', compact('pageTitle','withdrawMethod', 'user', 'withdraws', 'gateways'));
    }

    public function withdrawMethod(){
        $user = auth()->user();
        $pageTitle = 'Withdraw Method';
        $withdrawMethod = WithdrawMethod::where('status',Status::ENABLE)->get();
        return view('Template::user.withdraw.withdraw_method', compact('pageTitle', 'withdrawMethod', 'user'));
    }

    public function withdrawMethodSubmit(Request $request)
    {   
        $validation = [
            'method_code' => 'required',
            'amount' => 'required|numeric|gt:0'
        ];

        $method = WithdrawMethod::where('id', $request->method_code)->where('status', Status::ENABLE)->firstOrFail();
        $formData = $method->form->form_data;

        $user = auth()->user();
        $withdrawSetting = $user->withdrawSetting;

        $formProcessor = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);
        $validation = array_merge($validation,$validationRule);

        if(!$withdrawSetting){
            $withdrawSetting = new WithdrawSetting();
        }else{
            foreach(@$withdrawSetting->user_data ?? [] as $data){
                foreach($formData as $getData){
                    if($getData->name == $data->name && $data->value && $data->type == 'file' && $getData->type == 'file'){
                        @$validation[$getData->label][0] = 'nullable';
                    }
                }
            }
        }

        $request->validate($validation);

        if ($request->amount < $method->min_limit) {
            $notify[] = ['error', 'Your requested amount is smaller than minimum amount.'];
            return back()->withNotify($notify)->withInput();
        }
        if ($request->amount > $method->max_limit) {
            $notify[] = ['error', 'Your requested amount is larger than maximum amount.'];
            return back()->withNotify($notify)->withInput();
        }

        $userData = $formProcessor->processFormData($request, $formData);

        foreach(@$withdrawSetting->user_data ?? [] as $index => $data){
            foreach($formData as $getData){
                if($getData->name == $data->name && $data->value && $data->type == 'file' && $getData->type == 'file'){
                    if(!$userData[$index]['value']){
                        $userData[$index]['value'] = $data->value;
                    }
                }
            }
        }

        $withdrawSetting->user_id = $user->id;
        $withdrawSetting->withdraw_method_id = $method->id;
        $withdrawSetting->amount = $request->amount;
        $withdrawSetting->user_data = $userData;
        $withdrawSetting->next_withdraw_date = HolidayCalculator::nextWorkingDay($withdrawSetting);
        $withdrawSetting->save();

        $notify[] = ['success', 'Withdraw setting updated successfully'];
        return back()->withNotify($notify);
    }

    public function downloadAttachment($fileHash)
    {   
        try{
            $attachment = decrypt($fileHash); 
            $file = $attachment; 
            $path = getFilePath('verify');
            $full_path = $path . '/' . $file;
            $title = 'Attachment';
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $mimetype = mime_content_type($full_path);
            header('Content-Disposition: attachment; filename="' . $title . '.' . $ext . '";');
            header("Content-Type: " . $mimetype);
            return readfile($full_path);
        }catch(\Exception $error){
            $notify[] = ['error', $error->getMessage()];
            return back()->withNotify($notify);
        }
    }
}
