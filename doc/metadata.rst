Handling document metadata
==========================

PDF documents can hold general information, like the document's title, author and such, also known as metadata. To set
or retrieve them, you have to obtain the ``DocumentInformation`` object from the PDF writer::

    $information = $pdfWriter->getDocumentInformation();

You can then set or remove metadata with their respective methods::

    $information->set('Title', 'My awesome PDF document');
    $information->remove('Author');

When retrieving metadata, it can happen that these do not exist. Since BaconPdf follows a strict API, it will throw an
exception when the requested entry does not exist. You are advised to always check for the existence of the entry you
want to retrieve before actually retrieving it::

    if ($information->has('Title') {
        $title = $information->get('Title');
    } else {
        $title = '';
    }

Since the ``get()`` method will always return a string, there are two special cases in the metadata which must be
retrieved via special methods, namely ``CreationDate`` and ``ModDate``. Even though those entries have their own methods
for retrieval, checking their existence is still done via the ``has()`` method::

    if ($information->has('CreationDate')) {
        $creationDate = $information->getCreationDate();
    }

    if ($information->has('ModDate')) {
        $modificationDate = $information->getModificationDate();
    }

While the PDF specification names a list of standard entries in the metadata, it also allows arbitrary entries, thus the
``Info`` object does not distinguish between them, except for a few exceptions. Those exceptions are ``CreationDate`` and
``ModDate``, which cannot be set manually, but will always be manages by BaconPdf itself. Another exception is the
``Trapped`` entry, which is limited to three possible values (``True``, ``False`` and ``Unkown``). Every other entry can
be any kind of text string. Keep in mind that the key names are case-sensitive, so setting the standard-entries with all
lower-cased keys will not work. The standard entries are the following:

Standard PDF Metadata
---------------------

.. list-table::
   :widths: 1 9
   :header-rows: 1

   * - Key
     - Description
   * - ``Title``
     - The document's title.
   * - ``Author``
     - The name of the person who created the document.
   * - ``Subject``
     - The subject of the document.
   * - ``Keywords``
     - Keywords associated with the document.
   * - ``Creator``
     - If the document was converted to PDF from another format, the name of the application that created the original
       document from which it was converted. This would usually be the name of your application.
   * - ``Producer``
     - If the document was converted to PDF from another format, the name of the application that converted it to PDF.
       This entry defaults to "BaconPdf".
   * - ``Trapped``
     - Whether the document contains trapping information.

For more information on those entries, see chapter 10.2.1 of the PDF specification.
