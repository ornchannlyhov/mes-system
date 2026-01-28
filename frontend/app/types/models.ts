// User
export interface User {
    id: number
    name: string
    email: string
    avatar_url?: string
    role_id: number
    organization_id: number
    is_active: boolean
    role?: Role
    created_at: string
}

export interface Permission {
    id: number
    name: string
    label: string
}

export interface Role {
    id: number
    name: string
    label: string
    permissions?: Permission[]
}

// Product
export interface Product {
    id: number
    code: string
    name: string
    description?: string
    image_url?: string
    type: 'raw' | 'component' | 'finished'
    tracking: 'none' | 'lot' | 'serial'
    uom: string
    cost: number
    is_active: boolean
    on_hand?: number
    version: string
    used_in_boms?: Bom[]
    recent_mos?: ManufacturingOrder[]
}

// BOM
export interface Bom {
    id: number
    product_id: number
    product?: Product
    type: 'normal' | 'phantom'
    qty_produced: number
    is_active: boolean
    lines?: BomLine[]
    operations?: Operation[]
}

export interface BomLine {
    id: number
    bom_id: number
    product_id: number
    product?: Product
    quantity: number
    sequence: number
}

// Work Center
export interface WorkCenter {
    id: number
    code: string
    name: string
    location?: string  // Simple text field
    status: 'active' | 'maintenance' | 'down'
    efficiency_percent: number
    cost_per_hour: number
    overhead_per_hour: number
}

// Location
export interface Location {
    id: number
    code: string
    name: string
    type: 'warehouse' | 'production' | 'scrap'
}

// Lot
export interface Lot {
    id: number
    name: string
    product_id: number
    product?: Product
    expiry_date?: string
}

// Consumption (Material used in MO)
export interface Consumption {
    id: number
    manufacturing_order_id: number
    product_id: number
    product?: Product
    lot_id?: number
    lot?: Lot
    qty_planned: number
    qty_consumed: number
    variance?: number
    cost_impact?: number
    created_at?: string
}

// Manufacturing Order
export interface ManufacturingOrder {
    id: number
    name: string
    product_id: number
    product?: Product
    bom_id: number
    bom?: Bom
    qty_to_produce: number
    qty_produced: number
    status: 'draft' | 'scheduled' | 'confirmed' | 'in_progress' | 'done' | 'cancelled'
    priority: 'low' | 'normal' | 'high' | 'urgent'
    scheduled_start?: string
    scheduled_end?: string
    actual_start?: string
    actual_end?: string
    created_at: string
    work_orders?: WorkOrder[]
    consumptions?: Consumption[]
    lot_id?: number
    lot?: Lot
}

// Work Order
export interface WorkOrder {
    id: number
    manufacturing_order_id: number
    manufacturing_order?: ManufacturingOrder
    operation_id: number
    operation?: Operation
    work_center_id: number
    work_center?: WorkCenter
    status: 'pending' | 'ready' | 'in_progress' | 'paused' | 'done' | 'blocked'
    duration_expected: number
    duration_actual: number
    started_at?: string
    finished_at?: string
    qa_status?: 'pending' | 'pass' | 'fail'
    qa_comments?: string
    qa_by?: number
    qa_at?: string
    qa_user?: User
    assigned_user?: User

}

// Equipment
export interface Equipment {
    id: number
    code: string
    name: string
    work_center_id?: number
    work_center?: WorkCenter
    status: 'operational' | 'maintenance' | 'broken'
    last_maintenance?: string
    next_maintenance?: string
}

// Operation (BOM Routing)
export interface Operation {
    id: number
    bom_id: number
    work_center_id: number
    work_center?: WorkCenter
    name: string
    sequence: number
    duration_minutes: number
    needs_quality_check: boolean
    instructions?: string
    instruction_file_url?: string

}

// Paginated response
export interface PaginatedResponse<T> {
    data: T[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

// Cost Entry
export interface CostEntry {
    id: number
    manufacturing_order_id: number
    cost_type: 'material' | 'labor' | 'overhead' | 'scrap' | 'material_variance'
    description?: string
    product_id?: number
    product?: Product
    quantity?: number
    unit_cost: number
    total_cost: number
    notes?: string
    created_at: string
}

// Maintenance
export interface MaintenanceRequest {
    id: number
    name: string
    description?: string
    equipment_id: number
    equipment?: Equipment
    request_type: 'preventive' | 'corrective'
    scheduled_date?: string
    priority: string
    status: string
}

export interface MaintenanceSchedule {
    id: number
    name: string
    equipment_id: number
    equipment?: Equipment
    interval_days: number
    last_maintenance?: string
    next_maintenance?: string
    is_active: boolean
}

// Execution
export interface Scrap {
    id: number
    product_id: number
    product?: Product
    location_id?: number
    location?: Location
    quantity: number
    reason: string
    manufacturing_order_id?: number
    created_at: string
}

export interface UnbuildOrder {
    id: number
    name: string
    product_id: number
    product?: Product
    bom_id?: number
    bom?: Bom
    quantity: number
    status: string
    manufacturing_order_id?: number
    reason?: string
    created_at: string
}

// Inventory
export interface Serial {
    id: number
    name: string
    product_id: number
    product?: Product
    lot_id?: number
    lot?: Lot
    status: string
}

export interface Stock {
    id: number
    product_id: number
    product?: Product
    location_id: number
    location?: Location
    lot_id?: number
    lot?: Lot
    quantity: number
    reserved_qty: number
}

export interface StockAdjustment {
    id: number
    product_id: number
    product?: Product
    location_id: number
    location?: Location
    lot_id?: number | null
    lot?: Lot | null
    quantity: number
    reason: string
    reference?: string
    notes?: string
    user?: { name: string }
    created_at: string
}
