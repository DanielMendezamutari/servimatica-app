<?php
namespace App\Domain\Category;
interface CategoryRepositoryInterface {
    public function all(string $search=''): array;
    public function create(array $data): Category;
    public function update(int $id,array $data): Category;
    public function toggle(int $id): Category;
    public function delete(int $id): void;
    public function options(bool $owner): array;
}
