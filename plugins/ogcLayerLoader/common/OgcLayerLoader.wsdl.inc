      <!-- wmsLayerAutoLoader -->
      
      <complexType name="OgcLayerLoaderRequest">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="ogcLayers" type="types:MapOverlay"/>
          <element name="userLayers" type="types:ArrayOfUserLayer"/>
        </all>
      </complexType>
