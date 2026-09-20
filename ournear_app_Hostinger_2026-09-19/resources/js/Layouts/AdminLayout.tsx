import { Head } from '@inertiajs/react';
import { useState } from 'react';
import AdminContainer from '@/Components/Admin/AdminContainer';
import FlashMessage from '@/Components/Admin/FlashMessage';
import Sidebar from '@/Components/Admin/Sidebar';
import TopBar from '@/Components/Admin/TopBar';
import type { LayoutProps } from '@/types/layout';

export default function AdminLayout({ title, children }: LayoutProps) {
    const [sidebarOpen, setSidebarOpen] = useState(false);

    return (
        <div className="min-h-screen bg-canvas text-ink lg:flex">
            <Head title={title} />
            <Sidebar open={sidebarOpen} onClose={() => setSidebarOpen(false)} />
            <div className="flex min-h-screen min-w-0 flex-1 flex-col">
                <TopBar onMenuClick={() => setSidebarOpen(true)} />
                <main className="flex-1 overflow-x-hidden p-4 sm:p-6">
                    <AdminContainer>
                        <FlashMessage />
                        {children}
                    </AdminContainer>
                </main>
                <footer className="border-t border-line px-4 py-4 text-center text-xs text-muted sm:px-6">
                    OurNear Talent · Admin
                </footer>
            </div>
        </div>
    );
}
