import React, { useState } from 'react';
import { Warehouse } from '../types';

interface NewWarehouseModalProps {
    onClose: () => void;
    onAddWarehouse: (newWarehouse: Omit<Warehouse, 'WarehouseID'>) => void;
}

const NewWarehouseModal: React.FC<NewWarehouseModalProps> = ({ onClose, onAddWarehouse }) => {
    const [warehouseName, setWarehouseName] = useState('');
    const [location, setLocation] = useState('');
    const [warehouseType, setWarehouseType] = useState('Central');

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        onAddWarehouse({
            WarehouseName: warehouseName,
            Location: location,
            WarehouseType: warehouseType,
        });
        onClose();
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50 p-4">
            <div className="bg-[var(--color-bg-surface)] rounded-xl shadow-xl p-6 w-full max-w-md transform transition-all modal-content">
                <h3 className="text-xl font-semibold text-[var(--color-text-base)] mb-6">New Warehouse</h3>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label htmlFor="warehouseName" className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Warehouse Name</label>
                        <input
                            id="warehouseName"
                            type="text"
                            value={warehouseName}
                            onChange={(e) => setWarehouseName(e.target.value)}
                            required
                            className="form-input"
                        />
                    </div>
                    <div>
                        <label htmlFor="location" className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Location</label>
                        <input
                            id="location"
                            type="text"
                            value={location}
                            onChange={(e) => setLocation(e.target.value)}
                            required
                            className="form-input"
                        />
                    </div>
                    <div>
                        <label htmlFor="warehouseType" className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Warehouse Type</label>
                        <select
                            id="warehouseType"
                            value={warehouseType}
                            onChange={(e) => setWarehouseType(e.target.value)}
                            className="form-select"
                        >
                            <option>Central</option>
                            <option>Satellite</option>
                            <option>Cold Storage</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div className="flex justify-end space-x-3 pt-6 border-t border-[var(--color-border)]">
                        <button type="button" onClick={onClose} className="btn btn-secondary">Cancel</button>
                        <button type="submit" className="btn btn-primary">Add Warehouse</button>
                    </div>
                </form>
            </div>
        </div>
    );
};


interface WarehousePageProps {
    warehouses: Warehouse[];
    onAddWarehouse: (newWarehouse: Omit<Warehouse, 'WarehouseID'>) => void;
}

const WarehousePage: React.FC<WarehousePageProps> = ({ warehouses, onAddWarehouse }) => {
    const [isModalOpen, setIsModalOpen] = useState(false);

    return (
        <>
            <div className="bg-[var(--color-bg-surface)] p-6 md:p-8 rounded-xl shadow-md">
                <div className="flex justify-between items-center mb-6">
                    <h2 className="text-xl font-semibold text-[var(--color-text-base)]">Warehouse Management</h2>
                    <button onClick={() => setIsModalOpen(true)} className="btn btn-primary">
                        New Warehouse
                    </button>
                </div>
                <div className="table-wrapper">
                    <table className="custom-table">
                        <thead>
                            <tr>
                                <th scope="col">Warehouse ID</th>
                                <th scope="col">Warehouse Name</th>
                                <th scope="col">Location</th>
                                <th scope="col">Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            {warehouses.map((warehouse) => (
                                <tr key={warehouse.WarehouseID}>
                                    <td className="font-medium text-[var(--color-text-base)]">{warehouse.WarehouseID}</td>
                                    <td>{warehouse.WarehouseName}</td>
                                    <td>{warehouse.Location}</td>
                                    <td>{warehouse.WarehouseType}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
            {isModalOpen && <NewWarehouseModal onClose={() => setIsModalOpen(false)} onAddWarehouse={onAddWarehouse} />}
        </>
    );
};

export default WarehousePage;