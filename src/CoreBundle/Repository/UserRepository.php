<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\User;
use CoreBundle\Exception\ProgrammerException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends EntityRepository
{
    const PAGE_LIMIT = 10;

    /**
     * This will find one user by it's email, email is unique so it should only return one user.
     *
     * @param $email
     * @return User|null|object
     */
    public function findUserByEmail($email)
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Return null or a user
     *
     * @param $id
     * @return null|object|User
     */
    public function findByFacebookUserId($id)
    {
        return $this->findOneBy(['facebookUserId' => $id]);
    }

    /**
     * Return null or a user
     *
     * @param $id
     * @return null|object|User
     */
    public function findByGoogleUserId($id)
    {
        return $this->findOneBy(['googleUserId' => $id]);
    }

    /**
     * Returns a user with the right password token.
     * If multiple tokens are found in the db a programmer exception is thrown with a specific code.
     *
     * @param $token
     * @return null|User
     * @throws ProgrammerException
     */
    public function findUserByForgetPasswordToken($token)
    {
        try {
            $builder = $this->createQueryBuilder('u');

            return $builder->where($builder->expr()->eq('u.forgetPasswordToken', ':token'))
                ->andWhere($builder->expr()->isNotNull('u.forgetPasswordExpired'))
                ->andWhere('u.forgetPasswordExpired >= :today')
                ->setParameter('token', $token)
                ->setParameter('today', new \DateTime(), Type::DATETIME)
                ->getQuery()
                ->getSingleResult();

        } catch (NoResultException $ex) {
            // This means that nothing was found this thrown by the getSingleResult method
            return null;
        } catch (NonUniqueResultException $ex) {
            // this means that there was more then one result found and we have duplicate tokens in our database.
            // Look up the code for the error message in the logs
            throw new ProgrammerException('Duplicate Forget Password Token was found.', ProgrammerException::FORGET_PASSWORD_TOKEN_DUPLICATE_EXCEPTION_CODE);
        }
    }

    /**
     * Gets a paginated list of users
     *
     * @param null $searchString
     * @param int $page
     * @param int $limit
     *
     * @return Paginator
     */
    public function getUsers($searchString = null, $page = 1, $limit = 10)
    {
        $builder = $this->createQueryBuilder('u');

        if (!empty($searchString)) {
            $builder
                ->where($builder->expr()->like('u.email', ':searchString'))
                ->orWhere($builder->expr()->like('u.displayName', ':searchString'))
                ->setParameter('searchString', '%' .$searchString . '%');
        }

        $paginator = new Paginator($builder->getQuery());
        $paginator->getQuery()
            ->setMaxResults($limit)
            ->setFirstResult($limit * ($page - 1));

        return $paginator;
    }
}