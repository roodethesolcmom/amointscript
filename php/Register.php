<?php

namespace App\Controllers\Security;

use App\Model\Traits\UserValidate;
use App\Model\User;
use App\Service\Fenom;
use App\Service\Logger;
use App\Service\Mail;
use App\Service\AmoCRM;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Vesp\Controllers\Controller;

class Register extends Controller
{
    /** @var User $user */
    protected $user;
    use UserValidate;

    public function post(): ResponseInterface
    {
        if (!$email = filter_var(trim($this->getProperty('email')))) {
            return $this->failure('Вы должны указать правильный email');
        }

        if (!$password = trim($this->getProperty('password'))) {
            return $this->failure('Вы должны указать свой пароль');
        }

        if (strlen($password) < 6) {
            return $this->failure('Пароль должен быть не менее 6 символов');
        }

        $user = new User([
            'fullname' => trim($this->getProperty('fullname')),
            'password' => trim($this->getProperty('password')),
            'instagram' => trim($this->getProperty('instagram'), ' @'),
            'email' => $email,
            'active' => true,
            'role_id' => 3, // Regular user
        ]);
        
        $amokey = new AmoCRM();

        if ($promo = trim($this->getProperty('promo'))) {
            /** @var User $referrer */
            if (!$referrer = User::query()->where(['promo' => $promo, 'active' => true])->first()) {
                return $this->failure('Указан неправильный реферальный код');
            }

            $user->referrer_id = $referrer->id;
        }

        $validate = $this->validate($user);
        if ($validate !== true) {
            return $this->failure($validate);
        }

        if ($user->save()) {
            if ($user->email) {
                $secret = getenv('EMAIL_SECRET');
                $encrypted = base64_encode(@openssl_encrypt($user->email, 'AES-256-CBC', $secret));
                $this->sendMail($user, $encrypted);
                $amokey;
                
            }

            $user->makeTransaction(getenv('COINS_REGISTER'), 'register');

            return $this->success([
                'id' => $user->id,
            ]);
        }

        return $this->failure('Неизвестная ошибка');
    }

    protected function sendMail(User $user, string $secret): bool
    {
        $url = getenv('SITE_URL');
        $mail = new Mail();
        $fenom = new Fenom();

        try {
            $data = $user->toArray();
            $data['link'] = "{$url}service/email/confirm?user_id={$user->id}&secret={$secret}";
            if ($from = $this->getProperty('from')) {
                $data['link'] .= "&from={$from}";
            }

            $subject = 'Вы успешно зарегистрировались на Krafti.ru';
            $body = $fenom->fetch($mail->tpls['register'], $data);
        } catch (Exception $e) {
            (new Logger())->error('Could not fetch email template: ' . $e->getMessage());

            return false;
        }

        return $mail->send($user->email, $subject, $body);
    }
}
