<?php

namespace Dhensby\Zxcvbn;

use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\MemberPassword;
use SilverStripe\Security\PasswordValidator as BaseValidator;

class PasswordValidator extends BaseValidator
{
    /**
     * @param string $password
     * @param \SilverStripe\Security\Member $member
     * @return ValidationResult
     */
    public function validate($password, $member)
    {
        $result = ValidationResult::create();

        $this->testLength($password, $member, $result);
        $this->testScore($password, $member, $result);
        $this->testHistory($password, $member, $result);

        $this->extend('updateValidatePassword', $password, $member, $result, $this);

        return $result;
    }

    /**
     * @return \ZxcvbnPhp\Zxcvbn
     */
    protected function getScorer()
    {
        return new \ZxcvbnPhp\Zxcvbn();
    }

    /**
     * @param string $password
     * @param \SilverStripe\Security\Member $member
     * @param ValidationResult $result
     */
    protected function testScore($password, $member, $result)
    {
        $memberData = [
            'FirstName',
            'Surname',
            'Email',
        ];

        $userData = array_values(array_filter(
            $member->toMap(),
            function ($key) use ($memberData) {
                return in_array($key, $memberData);
            },
            ARRAY_FILTER_USE_KEY
        ));

        $strength = $this->getScorer()->passwordStrength($password, $userData);

        if ($strength['score'] < $this->getMinTestScore()) {
            $error = _t(
                __CLASS__ . '.LOWPASSWORDSTRENGTH',
                'Please increase password strength by making your password longer or more complex'
            );
            $result->addError($error, 'bad', 'LOW_CHARACTER_STRENGTH');
        }
    }

    /**
     * @param string $password
     * @param \SilverStripe\Security\Member $member
     * @param ValidationResult $result
     */
    protected function testLength($password, $member, $result)
    {
        $minLength = $this->getMinLength();
        if ($minLength && strlen($password) < $minLength) {
            $error = _t(
                __CLASS__ . '.TOOSHORT',
                'Password is too short, it must be {minimum} or more characters long',
                ['minimum' => $this->minLength]
            );

            $result->addError($error, 'bad', 'TOO_SHORT');
        }
    }

    /**
     * @param string $password
     * @param \SilverStripe\Security\Member $member
     * @param ValidationResult $result
     */
    protected function testHistory($password, $member, $result)
    {
        $historicCount = $this->getHistoricCount();
        if ($historicCount) {
            $previousPasswords = MemberPassword::get()
                ->where(['"MemberPassword"."MemberID"' => $member->ID])
                ->sort('"Created" DESC, "ID" DESC')
                ->limit($historicCount);
            /** @var MemberPassword $previousPassword */
            foreach ($previousPasswords as $previousPassword) {
                if ($previousPassword->checkPassword($password)) {
                    $error = _t(
                        __CLASS__ . '.PREVPASSWORD',
                        'You\'ve already used that password in the past, please choose a new password'
                    );
                    $result->addError($error, 'bad', 'PREVIOUS_PASSWORD');
                    break;
                }
            }
        }
    }

}
