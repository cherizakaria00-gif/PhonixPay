<?php

namespace App\View\Components;

use App\Models\Form;
use Illuminate\View\Component;

class WithdrawForm extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */

    public $identifier;
    public $identifierValue;
    public $form;
    public $formData;

    public $user;
    public $userData;

    public function __construct($identifier,$identifierValue)
    {
        $this->user = auth()->user();
        $this->userData = @$this->user->withdrawSetting->user_data ?? [];

        $this->identifier = $identifier;
        $this->identifierValue = $identifierValue;
        $this->form = Form::where($this->identifier,$this->identifierValue)->first();
        $this->formData = @$this->form->form_data ?? [];
    }
 
    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.withdraw-form');
    }
}
