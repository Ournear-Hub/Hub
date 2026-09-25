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
        <div className="flex h-screen overflow-hidden bg-canvas text-ink">
            <Head title={title} />
            <Sidebar open={sidebarOpen} onClose={() => setSidebarOpen(false)} />
            <div className="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
                <TopBar onMenuClick={() => setSidebarOpen(true)} />
                <main className="min-h-0 flex-1 overflow-y-auto p-4 sm:p-6">
                    <AdminContainer>
                        <FlashMessage />
                        {children}
                    </AdminContainer>
                </main>
                <footer className="shrink-0 border-t border-line px-4 py-4 text-center text-xs text-muted sm:px-6">
                    OurNear Talent · Admin
                </footer>
            </div>
        </div>
    );
}
