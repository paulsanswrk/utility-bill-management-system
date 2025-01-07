<?php

namespace App\Mail;

class UBMS_Mailable extends \Illuminate\Mail\Mailable
{


    protected string $website_url;
    protected string $login_link;
    protected string $app_name;
    protected string $app_name_hr;
    protected string $support_email;

    function __construct()
    {
        $this->website_url = rtrim(config('app.url'), '/');;
        $this->login_link = $this->website_url . '/login';

        $this->app_name = config('app.name');
        $this->app_name_hr = env('ubms_app_name_hr');


        $this->support_email = env('SUPPORT_EMAIL');
    }

    public function get_company_name($user_locale)
    {
        return $user_locale == 'en'? $this->app_name : $this->app_name_hr;
    }
}
