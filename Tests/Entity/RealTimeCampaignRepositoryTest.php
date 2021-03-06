<?php

namespace MauticPlugin\MauticContactSourceBundle\Tests\Entity;

use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use MauticPlugin\MauticContactSourceBundle\Entity\RealTimeCampaignRepository;

class RealTimeCampaignRepositoryTest extends AbstractMauticTestCase
{
    /*
     * @var RealTimeCampaignRepository
     */
    private $repo;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->repo = new RealTimeCampaignRepository($this->em);
    }

    //NOTE: Tests that require the 'activateRealTimeCampaign' method have to be
    //run first, because we cannot redefine/remove a constant.

    /** @test */
    public function it_uses_real_time_when_a_single_contact_id_is_set()
    {
        $this->repo = new RealTimeCampaignRepository($this->em);
        $limiter    = new ContactLimiter(1, 1234);
        $this->assertContains(1234, $this->repo->getPendingContactIds(1, $limiter));
    }

    /** @test */
    public function it_doesnt_use_real_time_if_constant_is_not_defined()
    {
        $contactIds = [1, 2, 3, 4, 5];
        $limiter    = new ContactLimiter(5, null, null, null, $contactIds, null, null, 5);

        $ids = $this->repo->getPendingContactIds(1, $limiter);

        // It pulls contact ids from the database, since there isn't any in the
        // database, it will be empty.
        $this->assertEmpty($ids);
    }

    /** @test */
    public function it_limits_the_contact_ids_returned_based_on_the_campaign_limit()
    {
        $this->activateRealTimeCampaign();
        $limiter = new ContactLimiter(5, null, null, null, [1, 2, 3, 4, 5], null, null, 2);
        $this->assertEquals([1, 2], $this->repo->getPendingContactIds(1, $limiter));
        $this->assertEmpty($this->repo->getPendingContactIds(1, $limiter));
        $this->assertEmpty($this->repo->getPendingContactIds(1, $limiter));
    }

    /** @test */
    public function it_reduces_the_returned_pending_contact_ids_based_on_batch_limit()
    {
        $this->activateRealTimeCampaign();
        $limiter = new ContactLimiter(2, null, null, null, [1, 2, 3, 4, 5], null, null, 6);
        $this->assertEquals([1, 2], $this->repo->getPendingContactIds(1, $limiter));
        $this->assertEquals([3, 4], $this->repo->getPendingContactIds(1, $limiter));
        $this->assertEquals([5], $this->repo->getPendingContactIds(1, $limiter));
        $this->assertEmpty($this->repo->getPendingContactIds(1, $limiter));
    }

    /** @test */
    public function it_returns_an_empty_array_when_all_contact_ids_have_been_used()
    {
        $this->activateRealTimeCampaign();
        $limiter = new ContactLimiter(4, null, null, null, [1, 2, 3, 4]);
        $this->assertEquals([1, 2, 3, 4], $this->repo->getPendingContactIds(1, $limiter));
        $this->assertEmpty($this->repo->getPendingContactIds(1, $limiter));
    }

    /** @test */
    public function it_reduces_the_campaign_limit_after_getting_pending_contact_ids()
    {
        $this->activateRealTimeCampaign();
        $limiter = new ContactLimiter(6, null, null, null, [1, 2, 3, 4, 5, 6], null, null, 10);
        $this->repo->getPendingContactIds(1, $limiter);
        $this->assertEquals(4, $limiter->getCampaignLimitRemaining());
    }

    private function activateRealTimeCampaign()
    {
        if (!defined(RealTimeCampaignRepository::MAUTIC_PLUGIN_CONTACT_SOURCE_REALTIME)) {
            define(RealTimeCampaignRepository::MAUTIC_PLUGIN_CONTACT_SOURCE_REALTIME, true);
        }
    }
}
