<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\Eloquent\CategoryModel;
class CategorySeeder extends Seeder {
    public function run(): void {
        foreach(['Laptops'=>'Computadoras portátiles y notebooks','Componentes'=>'Procesadores, memorias y discos',
            'Periféricos'=>'Teclados, ratones, monitores y audífonos','Accesorios'=>'Cables, fundas y adaptadores'] as $name=>$description) {
            CategoryModel::firstOrCreate(['name'=>$name],['description'=>$description,'status'=>'active']);
        }
    }
}
