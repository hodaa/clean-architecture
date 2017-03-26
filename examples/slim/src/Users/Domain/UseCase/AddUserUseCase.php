<?php

namespace Damianopetrungaro\CleanArchitectureSlim\Users\Domain\UseCase;

use Damianopetrungaro\CleanArchitecture\UseCase\Request\RequestInterface;
use Damianopetrungaro\CleanArchitecture\UseCase\Response\ResponseInterface;
use Damianopetrungaro\CleanArchitecture\UseCase\Validation\ValidableUseCaseInterface;
use Damianopetrungaro\CleanArchitectureSlim\Common\Error\ApplicationError;
use Damianopetrungaro\CleanArchitectureSlim\Common\Error\ApplicationErrorType;
use Damianopetrungaro\CleanArchitectureSlim\Users\Domain\Entity\UserEntity;
use Damianopetrungaro\CleanArchitectureSlim\Users\Domain\Mapper\UserMapperInterface;
use Damianopetrungaro\CleanArchitectureSlim\Users\Domain\Repository\Exception\UserPersistenceException;
use Damianopetrungaro\CleanArchitectureSlim\Users\Domain\Repository\UserRepositoryInterface;
use Damianopetrungaro\CleanArchitectureSlim\Users\Domain\ValueObjects\Email;
use Damianopetrungaro\CleanArchitectureSlim\Users\Domain\ValueObjects\Name;
use Damianopetrungaro\CleanArchitectureSlim\Users\Domain\ValueObjects\Password;
use Damianopetrungaro\CleanArchitectureSlim\Users\Domain\ValueObjects\Surname;

final class AddUserUseCase implements ValidableUseCaseInterface
{
    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;
    /**
     * @var UserMapperInterface
     */
    private $userMapper;

    /**
     * ListUsersUseCase constructor.
     * @param UserRepositoryInterface $userRepository
     * @param UserMapperInterface $userMapper
     */
    public function __construct(UserRepositoryInterface $userRepository, UserMapperInterface $userMapper)
    {
        $this->userRepository = $userRepository;
        $this->userMapper = $userMapper;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response): void
    {
        // If request is not valid set response as failed and return
        if (!$this->isValid($request, $response)) {
            $response->setAsFailed();
            return;
        }

        // Create new User using valueObjects
        $user = new UserEntity(
            $this->userRepository->nextId(),
            new Name($request->get('name')),
            new Surname($request->get('surname')),
            new Email($request->get('email')),
            new Password($request->get('password'))
        );

        try {
            // Add User to repository
            $this->userRepository->add($user);
        } catch (UserPersistenceException $e) {
            // If there's an error on saving set response as failed, add the error and return
            $response->setAsFailed();
            $response->addError('generic', new ApplicationError($e->getMessage(), ApplicationErrorType::PERSISTENCE_ERROR()));
            return;
        }

        // Transform User instances into array
        // Set the response as success, add the user to the response and return
        $user = $this->userMapper->toArray($user);
        $response->addData('user', $user);
        $response->setAsSuccess();
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(RequestInterface $request, ResponseInterface $response) : bool
    {
        try {
            $name = new Name($request->get('name', ''));
            unset($name);
        } catch (\InvalidArgumentException $e) {
            $response->addError('name', new ApplicationError($e->getMessage(), ApplicationErrorType::VALIDATION_ERROR()));
        }

        try {
            $surname = new Surname($request->get('surname', ''));
            unset($surname);
        } catch (\InvalidArgumentException $e) {
            $response->addError('surname', new ApplicationError($e->getMessage(), ApplicationErrorType::VALIDATION_ERROR()));
        }

        try {
            $email = new Email($request->get('email', ''));
            unset($email);
        } catch (\InvalidArgumentException $e) {
            $response->addError('email', new ApplicationError($e->getMessage(), ApplicationErrorType::VALIDATION_ERROR()));
        }

        try {
            $password = new Password($request->get('password', ''));
            unset($password);
        } catch (\InvalidArgumentException $e) {
            $response->addError('password', new ApplicationError($e->getMessage(), ApplicationErrorType::VALIDATION_ERROR()));
        }

        return !$response->hasErrors();
    }
}
