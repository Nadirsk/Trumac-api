<?php

namespace Database\Seeders;

use App\Models\DriverDocument;
use App\Models\User;
use App\Models\ValueList;
use Illuminate\Database\Seeder;

class DriverDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find users with "Driver" position (position_id 4 or any driver role)
        $drivers = User::where('position_id', 4)->get();

        if ($drivers->isEmpty()) {
            return;
        }

        // Get document types from ValueList (value_id = 1 contains document types)
        $docTypes = ValueList::where('value_id', 1)->where('is_deleted', false)->get();

        if ($docTypes->isEmpty()) {
            return;
        }

        $documents = [];

        foreach ($drivers as $driver) {
            foreach ($docTypes->take(2) as $index => $docType) {
                // status: 0 = Pending, 1 = Approved, 2 = Rejected
                $documents[] = [
                    'company_id' => 1,
                    'user_id' => $driver->id,
                    'doc_type_id' => $docType->id,
                    'doc_front_path' => 'drivers/documents/' . $docType->code . '_front_' . $driver->id . '.jpg',
                    'doc_back_path' => 'drivers/documents/' . $docType->code . '_back_' . $driver->id . '.jpg',
                    'doc_number' => strtoupper(substr(md5($driver->id . $docType->code), 0, 12)),
                    'status' => $index === 0 ? 1 : 0, // First doc approved, others pending
                    'is_active' => true,
                    'is_deleted' => false,
                ];
            }
        }

        foreach ($documents as $document) {
            DriverDocument::firstOrCreate(
                [
                    'user_id' => $document['user_id'],
                    'doc_type_id' => $document['doc_type_id'],
                ],
                $document
            );
        }
    }
}
