<?php

declare(strict_types=1);
namespace In2code\Powermail\Domain\Validator;

use In2code\Powermail\Domain\Model\Mail;
use In2code\Powermail\Exception\ClassDoesNotExistException;
use In2code\Powermail\Exception\InterfaceNotImplementedException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Result;

/**
 * ForeignValidator
 */
class ForeignValidator extends AbstractValidator
{
    /**
     * @var string
     */
    protected string $validatorInterface = ValidatorInterface::class;

    /**
     * Include foreign validators
     *
     * @param Mail $mail
     * @return bool
     * @throws ClassDoesNotExistException
     * @throws InterfaceNotImplementedException
     */
    public function isValid($mail)
    {
        foreach ((array)$this->settings['validators'] as $validatorConf) {
            $this->loadFile($validatorConf['require']);
            if (!class_exists($validatorConf['class'])) {
                throw new ClassDoesNotExistException(
                    'Class ' . $validatorConf['class'] . ' does not exists - check if file was loaded with autoloader',
                    1578609804
                );
            }
            if (is_subclass_of($validatorConf['class'], $this->validatorInterface)) {
                /** @var AbstractValidator $validator */
                $validator = GeneralUtility::makeInstance($validatorConf['class']);
                $validator->setConfiguration((array)$validatorConf['config']);
                $validator->initialize();
                /** @var Result $result */
                $this->addErrors($validator->validate($mail));
            } else {
                throw new InterfaceNotImplementedException(
                    'Validator does not implement ' . $this->validatorInterface,
                    1578609814
                );
            }
        }

        return $this->isValidState();
    }

    /**
     * Add errors and set validstate to false
     *
     * @param Result $result
     * @return void
     */
    protected function addErrors(Result $result): void
    {
        $errors = $result->getErrors();
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addError($error->getMessage(), $error->getCode(), $error->getArguments(), $error->getTitle());
            }
            $this->setValidState(false);
        }
    }

    /**
     * @param string|null $pathAndFile
     * @return void
     */
    protected function loadFile(string $pathAndFile = null): void
    {
        if (!empty($pathAndFile) && file_exists($pathAndFile)) {
            require_once($pathAndFile);
        }
    }
}
