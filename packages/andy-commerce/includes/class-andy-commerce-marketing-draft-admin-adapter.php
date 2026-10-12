<?php
/**
 * M3 draft-only admin adapter. Deliberately NOT registered with WordPress.
 * A later, separately reviewed integration will register endpoints after Dev gate.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class Andy_Commerce_M3_Draft_Admin_Adapter {
    private Andy_Commerce_M3_Draft_Repository $repository;
    public function __construct(Andy_Commerce_M3_Draft_Repository $repository) {
        $this->repository = $repository;
    }

    private function authorize(string $nonce): int {
        if (!current_user_can('manage_woocommerce')) {
            throw new DomainException('Insufficient permission');
        }
        if (!wp_verify_nonce($nonce, 'andy_commerce_m3_draft_write')) {
            throw new DomainException('Invalid draft nonce');
        }
        $actor = get_current_user_id();
        if ($actor < 1) { throw new DomainException('Invalid user'); }
        return $actor;
    }

    public function create(array $input, string $nonce): array {
        $actor = $this->authorize($nonce);
        return $this->repository->save($input, $actor);
    }

    public function revise(array $input, int $expectedRevision, string $nonce): array {
        $actor = $this->authorize($nonce);
        if ($expectedRevision < 1) { throw new DomainException('Invalid revision'); }
        return $this->repository->save($input, $actor, $expectedRevision);
    }

    /** Copy creates a new inactive campaign with its own revision/audit history. */
    public function duplicate(string $id,int $revision,string $nonce):array {
        $actor=$this->authorize($nonce);
        if (!preg_match('/^[a-z0-9][a-z0-9_-]{2,59}$/D',$id) || $revision<1) {
            throw new DomainException('Invalid draft to copy');
        }
        $newId='copy-'.strtolower(wp_generate_password(14,false,false));
        return $this->repository->duplicate($id,$revision,$actor,$newId);
    }

    public function archiveBatch(array $versions,string $nonce):int {
        $actor=$this->authorize($nonce);
        return $this->repository->archiveBatch($versions,$actor);
    }

    public function archive(string $campaignId, int $revision, string $nonce): void {
        $actor = $this->authorize($nonce);
        if (!preg_match('/^[a-z0-9][a-z0-9_-]{2,59}$/D', $campaignId)) {
            throw new DomainException('Invalid campaign identifier');
        }
        $this->repository->archive($campaignId, $revision, $actor);
    }
}
