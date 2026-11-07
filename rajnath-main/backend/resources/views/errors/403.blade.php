<x-layouts.error code="403" :title="__('You do not have access to this page')"
    :message="request()->is('admin', 'admin/*') ? 'Your role does not include this section. Ask the owner to give you the permission if you need it.' : __('This page is private or belongs to another account. If you think this is a mistake, sign in with the right account or contact us.')" />
