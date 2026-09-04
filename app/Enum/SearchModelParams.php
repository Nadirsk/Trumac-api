<?php

namespace App\Enum;

use BenSampo\Enum\Enum;

final class SearchModelParams extends Enum
{
    // if we doesn't add any values here it will be basically means name =  i can add any value like 'in' , 'between' etc name => 'in' something like this
    const newExample = [
        'id',
        'name',
        'description'
    ];

    const User = [
        'user_name',
        'first_name',
        'email',
        'phone',
        'position_id'  => '=',
        'franchise_id' => '=',
        'is_active'    => '=',
        'deleted_at'   => '=',
    ];

    // Trumac Distribution System
    const SkuCategory = [
        'name' => 'like',
        'description' => 'like',
        'is_active' => '=',
        'is_deleted' => '=',
        'parent_id' => '=',
    ];

    const Sku = [
        'code' => 'like',
        'name' => 'like',
        'description' => 'like',
        'category_id' => '=',
        'unit' => '=',
        'hsn_code' => 'like',
        'barcode' => 'like',
        'is_active' => '=',
        'is_deleted' => '=',
    ];

    const Location = [
        'name' => 'like',
        'address' => 'like',
        'city' => 'like',
        'state' => 'like',
        'pincode' => 'like',
        'type' => '=',
        'is_active' => '=',
        'is_deleted' => '=',
    ];

    const Warehouse = [
        'name' => 'like',
        'code' => 'like',
        'contact_person' => 'like',
        'phone' => 'like',
        'email' => 'like',
        'location_id' => '=',
        'is_active' => '=',
        'is_deleted' => '=',
    ];

    const CompanyGodown = [
        'name' => 'like',
        'code' => 'like',
        'contact_person' => 'like',
        'phone' => 'like',
        'warehouse_id' => '=',
        'location_id' => '=',
        'is_active' => '=',
        'is_deleted' => '=',
    ];

    const Franchise = [
        'name' => 'like',
        'code' => 'like',
        'owner_name' => 'like',
        'phone' => 'like',
        'email' => 'like',
        'gst_no' => 'like',
        'warehouse_id' => '=',
        'location_id' => '=',
        'is_active' => '=',
        'is_deleted' => '=',
    ];

    const Retailer = [
        'name' => 'like',
        'shop_name' => 'like',
        'phone' => 'like',
        'email' => 'like',
        'gst_no' => 'like',
        'franchise_id' => '=',
        'company_godown_id' => '=',
        'location_id' => '=',
        'is_flagged' => '=',
        'is_active' => '=',
        'is_deleted' => '=',
        'created_by' => '=',
    ];

    const Vendor = [
        'name' => 'like',
        'contact_person' => 'like',
        'phone' => 'like',
        'email' => 'like',
        'gst_no' => 'like',
        'pan_no' => 'like',
        'is_active' => '=',
        'is_deleted' => '=',
    ];

    const Attendance = [
        'user_id' => '=',
        'date' => '=',
        'status' => '=',
    ];

    const LeaveType = [
        'name' => 'like',
        'is_paid' => '=',
        'is_active' => '=',
        'is_deleted' => '=',
    ];

    const LeaveRequest = [
        'user_id' => '=',
        'leave_type_id' => '=',
        'status' => 'in',
    ];

    const Pjp = [
        'employee_id' => '=',
        'franchise_id' => '=',
        'day_of_week' => '=',
        'name' => 'like',
        'is_active' => '=',
        'is_deleted' => '=',
    ];

    const PjpChange = [
        'pjp_id' => '=',
        'retailer_id' => '=',
        'date' => '=',
        'action' => '=',
        'status' => '=',
        'requested_by' => '=',
    ];

    const SalesOrder = [
        'order_number' => 'like',
        'retailer_id'  => '=',
        'source_type'  => '=',
        'source_id'    => '=',
        'driver_id'    => '=',
        'status'       => 'in',
        'order_date'   => 'between',
        'created_by'   => '=',
        'is_deleted'   => '=',
    ];

    const CashCollection = [
        'retailer_id' => '=',
        'order_id' => '=',
        'collected_by' => '=',
        'status' => '=',
        'collection_date' => 'between',
    ];

    const JourneyPlan = [
        'driver_id' => '=',
        'date' => '=',
        'status' => '=',
    ];

    const Inventory = [
        'sku_id' => '=',
        'sku.name' => 'like',
        'location_type' => '=',
        'location_id' => '=',
    ];

    const PurchaseOrder = [
        'po_number' => 'like',
        'vendor_id' => '=',
        'status' => 'in',
        'order_date' => 'between',
        'created_by' => '=',
    ];

    const Grn = [
        'grn_number' => 'like',
        'reference_type' => '=',
        'reference_id' => '=',
        'location_type' => '=',
        'location_id' => '=',
        'status' => '=',
        'received_by' => '=',
        'received_date' => 'between',
    ];

    const PurchaseInvoice = [
        'invoice_number' => 'like',
        'grn_id' => '=',
        'requisition_id' => '=',
        'from_location_type' => '=',
        'to_location_type' => '=',
        'status' => '=',
        'invoice_date' => 'between',
    ];

    const Requisition = [
        'requisition_number' => 'like',
        'from_location_type' => '=',
        'from_location_id' => '=',
        'to_location_type' => '=',
        'to_location_id' => '=',
        'status' => 'in',
        'requested_by' => '=',
    ];

    const Position = [
        'name' => 'like',
        'role_id' => '=',
        'is_active' => '=',
        'is_deleted' => '=',
    ];

    const Notification = [
        'user_id' => '=',
        'type' => '=',
        'is_read' => '=',
    ];

    const Role = [
        'name' => 'like',
        'is_active' => '=',
        'is_deleted' => '=',
    ];

    const Module = [
        'name' => 'like',
        'is_active' => '=',
        'is_deleted' => '=',
    ];

    const Permission = [
        'name' => 'like',
        'module_id' => '=',
        'is_active' => '=',
        'is_deleted' => '=',
    ];

    const Version = [
        'version' => 'like',
    ];

    const Value = [
        'name' => 'like',
        'is_active' => '=',
        'is_deleted' => '=',
    ];

    const ValueList = [
        'description' => 'like',
        'code' => 'like',
        'value_id' => '=',
        'is_active' => '=',
        'is_deleted' => '=',
    ];

    const UserTimestamp = [
        'user_id' => '=',
        'name' => 'like',
        'url' => 'like',
    ];

    const PositionPermission = [
        'position_id' => '=',
        'permission_id' => '=',
        'is_active' => '=',
        'is_deleted' => '=',
    ];

    const DriverDocument = [
        'user_id' => '=',
        'doc_type_id' => '=',
        'status' => '=',
        'is_active' => '=',
        'is_deleted' => '=',
    ];

    const Company = [
        'name' => 'like',
        'email' => 'like',
        'phone' => 'like',
        'is_active' => '=',
        'is_deleted' => '=',
    ];

    const Questionnaire = [
        'name' => 'like',
        'description' => 'like',
        'is_active' => '=',
        'is_deleted' => '=',
    ];

    const Question = [
        'questionnaire_id' => '=',
        'question_text' => 'like',
        'type' => '=',
        'is_active' => '=',
        'is_deleted' => '=',
    ];

    const QuestionnaireResponse = [
        'questionnaire_id' => '=',
        'retailer_id' => '=',
        'responded_by' => '=',
    ];

    const RetailerVisit = [
        'user_id' => '=',
        'retailer_id' => '=',
        'pjp_id' => '=',
        'visit_type' => '=',
        'visit_date' => 'between',
        'is_active' => '=',
        'is_deleted' => '=',
    ];

    const Invoice = [
        'invoice_number' => 'like',
        'sales_order_id' => '=',
        'retailer_id' => '=',
        'status' => 'in',
        'invoice_date' => 'between',
        'due_date' => 'between',
        'created_by' => '=',
        'is_deleted' => '=',
    ];

    const PaymentReceived = [
        'payment_number' => 'like',
        'invoice_id' => '=',
        'retailer_id' => '=',
        'payment_mode' => '=',
        'status' => '=',
        'payment_date' => 'between',
        'is_deleted' => '=',
    ];

    const Challan = [
        'challan_number' => 'like',
        'requisition_id' => '=',
        'journey_plan_id' => '=',
        'from_location_type' => '=',
        'from_location_id' => '=',
        'to_location_type' => '=',
        'to_location_id' => '=',
        'driver_id' => '=',
        'status' => 'in',
        'is_deleted' => '=',
    ];
}
