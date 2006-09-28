      <!-- wmsBrowser -->
      
      <complexType name="WmsBrowserRequest">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="wmsLayers" type="types:ArrayOfLayerOverlay"/>
          <element name="userLayers" type="types:ArrayOfUserLayer"/>
        </all>
      </complexType>

      <complexType name="WmsBrowserResult"/>
