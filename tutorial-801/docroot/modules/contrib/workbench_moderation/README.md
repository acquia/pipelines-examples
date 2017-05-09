Workbench Moderation
====================

About this module
-----------------
Workbench Moderation (WBM) provides basic moderation support for revisionable content entities.  That is, it allows
site administrators to define States that content can be in, Transitions between those States, and rules around who
is able to Transition content from one State to another, when.

In concept, any revision-aware content entity is supportable.  In core, that includes Nodes and Block Content. However,
there may be a small amount of work needed to support additional content entities due to inconsistencies in the
Entity API.core. To add custom WBM integration for your entity type, annotate your entity to specify a moderation
handler class that extends \Drupal\workbench_moderation\Entity\Handler\ModerationHandler.

Installation
------------

WBM has no special installation instructions. The default configuration that ships with the module should cover most
typical use cases, with no need to configure additional States or Transitions. You are welcome to do so, however, and
to edit or remote any pre-defined State or Transition.

Note that when an entity is under moderation by this module, explicitly setting its published state (on nodes) or its
"make default revision" value (on all entities) will have no effect, because WBM will always overwrite those values based
on your configured State rules.  That is most notable with regards to core's Publish and Unpublish actions for Bulk
Operations on nodes, as seen on /admin/content.  Those actions will simply have no effect on a moderated node.

To avoid confusion, removing the actions from that view (or any similar views) is recommended.

1) Visit /admin/structure/views/view/content .
2) Configure: 'Content: Node operations bulk form' > change the Available actions to 'Only selected actions' > Deselect 'publish content' and 'unpublish content'.
3) Save view.
