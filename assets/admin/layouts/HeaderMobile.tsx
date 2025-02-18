import React from 'react';

const sidebarShow = () => {
    const body = document.querySelector('body');
    if (body) body.classList.toggle('sidebar-show');
};

export default function HeaderMobile() {
    return (
        <div className="main-mobile-header doc-mobile-header">
            <div onClick={sidebarShow} className="menu-link">
                <i className="ri-menu-line"></i>
            </div>
        </div>
    );
}
